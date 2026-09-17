<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetStaging;
use App\Models\AuditEvent;
use App\Models\Room;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use App\Services\AssetIdentityService;
use App\Services\SpreadsheetReader;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasRole(AccessScope::COORDINATORS), 403);
        $batch = $request->filled('batch') ? WorkRecord::where('kind', 'import')->where('created_by', $request->user()->id)->findOrFail($request->integer('batch')) : null;

        return Inertia::render('Operations/Import', [
            'batches' => WorkRecord::where('kind', 'import')->where('created_by', $request->user()->id)->latest()->paginate(10)->through(fn (WorkRecord $item): array => $item->only(['id', 'title', 'status', 'created_at'])),
            'batch' => $batch?->only(['id', 'title', 'status', 'created_at']), 'rows' => $batch ? AssetStaging::where('import_batch_id', (string) $batch->id)->paginate(50)->withQueryString()->through(fn (AssetStaging $row) => [...$row->toArray(), 'row_checksum' => hash('sha256', json_encode($row->raw_data))]) : null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole(AccessScope::COORDINATORS), 403);
        $request->validate(['file' => 'required|file|mimes:csv,txt,xlsx|max:10240', 'sheet' => 'nullable|string|max:100', 'column_map' => 'nullable|json|max:5000']);
        $inputRows = app(SpreadsheetReader::class)->read($request->file('file'), $request->input('sheet'));
        $header = array_shift($inputRows);
        abort_unless(is_array($header), 422, 'Berkas kosong.');
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
        $mapping = json_decode($request->input('column_map') ?: '{}', true);
        abort_unless(is_array($mapping) && collect($mapping)->every(fn ($value) => is_string($value)), 422, 'Pemetaan kolom harus berupa objek teks.');
        $header = array_map(fn ($column) => $mapping[trim($column)] ?? trim($column), $header);
        $required = ['name', 'category_id', 'room_id', 'item_code', 'nup', 'satker_code', 'condition'];
        abort_unless(count(array_unique($header)) === count($header) && ! array_diff($required, $header), 422, 'Header CSV wajib: '.implode(', ', $required));
        $rows = [];
        $seen = [];
        foreach ($inputRows as $values) {
            if ($values === [null]) {
                continue;
            }
            abort_if(count($rows) >= 1000, 422, 'Satu batch maksimal 1.000 baris.');
            $raw = count($header) === count($values) ? array_combine($header, $values) : [];
            $validator = Validator::make($raw, $this->rules($request));
            $errors = $validator->errors()->all();
            $key = implode('|', [$raw['satker_code'] ?? '', $raw['item_code'] ?? '', $raw['nup'] ?? '']);
            if (isset($seen[$key]) || $this->duplicate($raw)) {
                $errors[] = 'Identitas satker/kode/NUP duplikat.';
            }
            $seen[$key] = true;
            $rows[] = ['raw_data' => $raw, 'status' => $errors ? 'error' : 'validated', 'validation_errors' => implode(' ', $errors)];
        }
        abort_unless(count($rows), 422, 'CSV tidak berisi data.');
        $sourcePath = $request->file('file')->store('imports/sources', 'local');
        abort_unless($sourcePath, 503);
        try {
            $batch = DB::transaction(function () use ($rows, $request, $sourcePath) {
                $checksum = hash_file('sha256', $request->file('file')->getRealPath());
                $previous = WorkRecord::where('kind', 'import')->where('created_by', $request->user()->id)->where('data->checksum', $checksum)->where('status', 'committed')->first();
                abort_if($previous !== null, 422, 'Berkas ini sudah pernah diimpor.');
                $batch = WorkRecord::create(['kind' => 'import', 'title' => mb_substr($request->file('file')->getClientOriginalName(), 0, 255), 'status' => 'preview', 'created_by' => $request->user()->id, 'data' => ['checksum' => $checksum, 'source_path' => $sourcePath, 'sheet' => $request->input('sheet'), 'total' => count($rows)]]);
                foreach ($rows as $row) {
                    AssetStaging::create(['import_batch_id' => (string) $batch->id, ...$row]);
                }
                AuditEvent::record($batch, 'import.previewed');

                return $batch;
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($sourcePath);
            throw $exception;
        }

        return to_route('imports.index', ['batch' => $batch->id])->with('success', 'Pratinjau siap. Data aset belum berubah.');
    }

    public function commit(Request $request, WorkRecord $batch): RedirectResponse
    {
        abort_unless($request->user()->hasRole(AccessScope::COORDINATORS) && $batch->kind === 'import' && $batch->created_by === $request->user()->id, 403);
        DB::transaction(function () use ($request, $batch) {
            app(AssetIdentityService::class)->lock();
            Room::whereIn('id', app(AccessScope::class)->roomIds($request->user(), true, AccessScope::COORDINATORS))->orderBy('id')->lockForUpdate()->get();
            $locked = WorkRecord::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'preview', 422, 'Batch sudah diproses.');
            $rows = AssetStaging::where('import_batch_id', (string) $batch->id)->lockForUpdate()->get();
            abort_if($rows->contains('status', 'error'), 422, 'Perbaiki baris yang ditandai sebelum menyimpan batch.');
            foreach ($rows as $row) {
                $data = Validator::make($row->raw_data, $this->rules($request))->validate();
                app(AssetIdentityService::class)->assertAvailable($data);
                abort_if($this->duplicate($data), 422, 'Identitas aset kini sudah digunakan. Tidak ada baris yang diimpor.');
                $asset = Asset::create([...$data, 'status' => 'active', 'is_loanable' => false]);
                $row->update(['status' => 'committed']);
                AuditEvent::record($asset, 'asset.imported', ['batch_id' => $batch->id], $asset->room?->unit_id);
            }
            $locked->update(['status' => 'committed']);
            AuditEvent::record($locked, 'import.committed');
        }, 3);

        return back()->with('success', 'Seluruh baris valid berhasil diimpor.');
    }

    public function correct(Request $request, WorkRecord $batch, AssetStaging $row): RedirectResponse
    {
        abort_unless($request->user()->hasRole(AccessScope::COORDINATORS) && $batch->kind === 'import' && $batch->created_by === $request->user()->id, 403);
        abort_unless((string) $row->import_batch_id === (string) $batch->id, 404);
        $data = $request->validate([...$this->rules($request), 'reason' => 'required|string|max:5000', 'row_checksum' => 'required|string|size:64']);
        DB::transaction(function () use ($request, $batch, $row, $data) {
            $locked = WorkRecord::whereKey($batch->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'preview', 422, 'Batch sudah diproses dan tidak dapat diubah.');
            $staging = AssetStaging::whereKey($row->id)->lockForUpdate()->firstOrFail();
            abort_unless(hash_equals(hash('sha256', json_encode($staging->raw_data)), $data['row_checksum']), 422, 'Baris sudah berubah. Muat ulang pratinjau.');
            $before = $staging->raw_data;
            $staging->update(['raw_data' => collect($data)->except(['reason', 'row_checksum'])->all()]);
            $seen = [];
            foreach (AssetStaging::where('import_batch_id', (string) $batch->id)->orderBy('id')->get() as $candidate) {
                $errors = Validator::make($candidate->raw_data, $this->rules($request))->errors()->all();
                $key = implode('|', [$candidate->raw_data['satker_code'] ?? '', $candidate->raw_data['item_code'] ?? '', $candidate->raw_data['nup'] ?? '']);
                if (isset($seen[$key]) || $this->duplicate($candidate->raw_data)) {
                    $errors[] = 'Identitas satker/kode/NUP duplikat.';
                }
                $seen[$key] = true;
                $candidate->update(['status' => $errors ? 'error' : 'validated', 'validation_errors' => implode(' ', $errors)]);
            }
            AuditEvent::record($staging, 'import.row_corrected', ['batch_id' => $batch->id, 'before' => $before, 'after' => $staging->raw_data, 'reason' => $data['reason']]);
        });

        return back()->with('success', 'Koreksi tersimpan dan seluruh baris diperiksa ulang. Aset belum diimpor.');
    }

    private function rules(Request $request): array
    {
        return [
            'name' => 'required|string|max:255', 'category_id' => 'required|integer|exists:asset_categories,id',
            'room_id' => ['required', 'integer', Rule::in(app(AccessScope::class)->roomIds($request->user(), true, AccessScope::COORDINATORS))],
            'item_code' => 'required|string|max:255', 'nup' => 'required|string|max:255', 'satker_code' => 'required|string|max:100',
            'condition' => 'required|in:Baik,Rusak Ringan,Rusak Berat', 'brand_type' => 'nullable|string|max:255', 'value' => 'nullable|numeric|min:0|max:9999999999999.99',
        ];
    }

    private function duplicate(array $data): bool
    {
        return Asset::where('satker_code', $data['satker_code'] ?? '')->where('item_code', $data['item_code'] ?? '')->where('nup', $data['nup'] ?? '')->exists();
    }
}
