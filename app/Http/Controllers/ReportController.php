<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AuditEvent;
use App\Models\Bast;
use App\Models\Room;
use App\Models\WorkRecord;
use App\Services\AccessScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): Response
    {
        abort_unless($request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 403);

        return Inertia::render('Operations/Reports', [
            'reports' => WorkRecord::where('kind', 'report_snapshot')->where('created_by', $request->user()->id)->latest()->paginate(15)->through(fn ($record) => [...$record->only(['id', 'title', 'created_at']), 'count' => count($record->data['snapshot']), 'period' => $record->data['period']]),
            'rooms' => Room::whereIn('id', $this->scope->roomIds($request->user(), true))->get(['id', 'name']),
            'categories' => AssetCategory::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 403);
        $data = $request->validate(['title' => 'required|string|max:255', 'period' => 'required|date_format:Y-m', 'room_id' => ['nullable', Rule::in($this->scope->roomIds($request->user(), true))], 'category_id' => 'nullable|integer|exists:asset_categories,id', 'condition' => 'nullable|in:Baik,Rusak Ringan,Rusak Berat']);
        DB::transaction(function () use ($request, $data) {
            $assets = $this->scope->assets($request->user(), true)->with(['room', 'category'])
                ->when($data['room_id'] ?? null, fn ($query, $id) => $query->where('room_id', $id))
                ->when($data['category_id'] ?? null, fn ($query, $id) => $query->where('category_id', $id))
                ->when($data['condition'] ?? null, fn ($query, $condition) => $query->where('condition', $condition))
                ->orderBy('id')->limit(1001)->lockForUpdate()->get();
            abort_unless($assets->isNotEmpty() && $assets->count() <= 1000, 422, 'Pilih filter berisi 1–1.000 aset.');
            $snapshot = $assets->map(fn ($asset) => [...$asset->only(['id', 'name', 'satker_code', 'item_code', 'nup', 'condition', 'status', 'value']), 'room' => $asset->room?->name, 'category' => $asset->category?->name])->all();
            $record = WorkRecord::create(['kind' => 'report_snapshot', 'title' => $data['title'], 'status' => 'draft', 'created_by' => $request->user()->id, 'data' => ['period' => $data['period'], 'filters' => collect($data)->except('title')->all(), 'asset_ids' => $assets->pluck('id')->all(), 'snapshot' => $snapshot, 'checksum' => $this->checksum($snapshot), 'captured_at' => now()->toIso8601String(), 'issuer' => $request->user()->name]]);
            AuditEvent::record($record, 'report.snapshot_created', ['count' => $assets->count()]);
        }, 3);

        return back()->with('success', 'Snapshot disimpan. Perubahan master berikutnya tidak mengubah arsip ini.');
    }

    public function download(Request $request, WorkRecord $report, string $format): mixed
    {
        abort_unless(in_array($format, ['pdf', 'xlsx']), 404);
        abort_unless($report->kind === 'report_snapshot' && $report->created_by === $request->user()->id && $request->user()->hasRole([...AccessScope::COORDINATORS, ...AccessScope::KEEPERS]), 404);
        $ids = $report->data['asset_ids'];
        abort_unless(count($ids) > 0 && $this->scope->assets($request->user(), true)->whereKey($ids)->count() === count($ids), 403, 'Cakupan akses laporan telah berubah.');
        $snapshot = $report->data['snapshot'];
        abort_unless(hash_equals($report->data['checksum'], $this->checksum($snapshot)), 409, 'Integritas snapshot tidak sesuai.');
        AuditEvent::record($report, 'report.downloaded', ['format' => $format]);
        if ($format === 'pdf') {
            $document = new Bast(['bast_number' => 'SNAPSHOT-'.$report->id, 'bast_type' => 'report', 'status' => 'draft', 'created_at' => $report->created_at, 'snapshot' => ['title' => $report->title, 'header' => config('simon.document_header'), 'purpose' => 'Label periode '.$report->data['period'].'; data direkam '.$report->data['captured_at'].'. Bukan rekonstruksi saldo historis atau pengesahan resmi.', 'issuer' => $report->data['issuer'], 'receiver' => '', 'items' => $snapshot]]);

            return Pdf::setOptions(['isRemoteEnabled' => false, 'isPhpEnabled' => false])->loadView('pdf.bast', ['bast' => $document])->download('laporan-'.$report->id.'.pdf');
        }

        return response()->streamDownload(function () use ($snapshot, $report) {
            $book = new Spreadsheet;
            try {
                $sheet = $book->getActiveSheet();
                $sheet->setTitle('Snapshot aset');
                $rows = [['Laporan', $report->title], ['Label periode', $report->data['period']], ['Direkam pada', $report->data['captured_at']], ['Catatan', 'Snapshot saat dibuat; bukan saldo historis atau pengesahan resmi.'], ['ID internal', 'Nama', 'Satker', 'Kode barang', 'NUP', 'Ruangan', 'Kategori', 'Kondisi', 'Status', 'Nilai']];
                foreach ($snapshot as $asset) {
                    $rows[] = array_map(fn ($key) => $asset[$key] ?? '', ['id', 'name', 'satker_code', 'item_code', 'nup', 'room', 'category', 'condition', 'status', 'value']);
                }
                foreach ($rows as $rowIndex => $row) {
                    foreach ($row as $columnIndex => $value) {
                        $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 1], (string) $value, DataType::TYPE_STRING);
                    }
                }
                $sheet->freezePane('A6');
                (new Xlsx($book))->save('php://output');
            } finally {
                $book->disconnectWorksheets();
            }
        }, 'laporan-'.$report->id.'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Cache-Control' => 'private, no-store']);
    }

    private function checksum(array $snapshot): string
    {
        return hash('sha256', json_encode(array_map(function (array $item): array {
            ksort($item);

            return $item;
        }, $snapshot), JSON_THROW_ON_ERROR));
    }
}
