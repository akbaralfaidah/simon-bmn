<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AuditEvent;
use App\Models\EmployeeProfile;
use App\Models\OrganizationUnit;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\Room;
use App\Models\User;
use App\Notifications\AccountDeleted;
use App\Notifications\AccountStatusUpdated;
use App\Notifications\AssignmentUpdated;
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdministrationController extends Controller
{
    public function __construct(private AccessScope $scope) {}

    public function index(Request $request): Response
    {
        abort_unless($this->scope->administer($request->user()), 403);
        $units = OrganizationUnit::whereIn('id', $this->scope->administrativeUnitIds($request->user()));

        return Inertia::render('Operations/Administration', [
            'users' => User::with(['profile', 'roleAssignments.role'])
                ->when(! $this->scope->global($request->user()), fn ($query) => $query->whereHas('profile', fn ($query) => $query->whereIn('unit_id', (clone $units)->select('id'))))
                ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
                ->latest()
                ->paginate(20)
                ->withQueryString(),
            'units' => $units->orderBy('name')->get(), 'rooms' => Room::whereIn('unit_id', $this->scope->administrativeUnitIds($request->user()))->orderBy('name')->get(),
            'categories' => AssetCategory::orderBy('name')->get(), 'isGlobal' => $this->scope->global($request->user()),
        ]);
    }

    public function user(Request $request, User $user): RedirectResponse
    {
        $unitIds = $this->scope->administrativeUnitIds($request->user());
        abort_unless($this->scope->administer($request->user()) && ($this->scope->global($request->user()) || in_array($user->profile?->unit_id, $unitIds)), 403);
        if (! $this->scope->global($request->user())) {
            abort_if($user->roleAssignments()->where(function ($query) use ($unitIds) {
                $query->where('is_global', true)->orWhereNotIn('unit_id', $unitIds)->orWhereNull('unit_id');
            })->exists(), 403, 'Akun lintas unit hanya dapat dikelola admin global.');
        }
        abort_if($user->id === $request->user()->id, 403, 'Perubahan akun sendiri harus dilakukan pengelola lain.');
        $data = $request->validate([
            'status' => 'required|in:active,suspended,pending',
            'name' => 'nullable|string|max:255',
            'nip' => [
                'nullable',
                'string',
                'max:30',
                function ($attribute, $value, $fail) use ($user) {
                    if ($value !== null && (str_contains($value, '@') || $value === $user->email)) {
                        $fail('Format NIP tidak boleh berupa alamat email.');
                    }
                },
            ],
            'phone' => 'nullable|string|max:30',
            'role' => ['nullable', Rule::in(['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'])],
            'unit_id' => ['nullable', Rule::in($unitIds)],
            'room_id' => ['nullable', Rule::in(Room::whereIn('unit_id', $unitIds)->pluck('id')->all())],
            'ends_at' => 'nullable|date|after:today',
            'reason' => $request->input('status') === 'active' && empty($request->input('role'))
                ? 'nullable|string|max:5000'
                : 'required|string|max:5000',
        ]);

        if (empty($data['reason'])) {
            $data['reason'] = $data['status'] === 'active'
                ? 'Verifikasi dan aktivasi akun pengguna oleh pengelola.'
                : 'Pembaruan data / status akun.';
        }
        if (($data['role'] ?? null) === 'Koordinator') {
            abort_unless($this->scope->global($request->user()), 403, 'Penugasan Koordinator memerlukan mandat admin global.');
            $request->validate(['current_password' => 'required|current_password']);
        }
        if ($data['status'] === 'active') {
            abort_unless($user->hasVerifiedEmail(), 422, 'Pengguna perlu memverifikasi email terlebih dahulu.');
        }
        if (! empty($data['role'])) {
            abort_unless(! empty($data['unit_id']), 422, 'Unit penugasan wajib dipilih.');
            if (! empty($data['room_id'])) {
                abort_unless(Room::whereKey($data['room_id'])->where('unit_id', $data['unit_id'])->exists(), 422);
            }
        }
        DB::transaction(function () use ($user, $data, $request) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $userUpdates = ['status' => $data['status']];
            if ($request->filled('name')) {
                $userUpdates['name'] = trim((string) $request->input('name'));
            }
            $locked->update($userUpdates);

            $profileUpdates = [];
            if (! empty($data['unit_id'])) {
                $profileUpdates['unit_id'] = $data['unit_id'];
            }
            if ($request->has('nip')) {
                $rawNip = trim((string) $request->input('nip'));
                $profileUpdates['nip'] = (! empty($rawNip) && ! str_contains($rawNip, '@')) ? $rawNip : null;
            }
            if ($request->has('phone')) {
                $rawPhone = trim((string) $request->input('phone'));
                $profileUpdates['phone'] = ! empty($rawPhone) ? $rawPhone : null;
            }
            if (! empty($profileUpdates)) {
                EmployeeProfile::updateOrCreate(['user_id' => $locked->id], $profileUpdates);
            }
            if (! empty($data['role'])) {
                $role = Role::firstOrCreate(['name' => $data['role']]);
                $identity = ['user_id' => $locked->id, 'role_id' => $role->id, 'unit_id' => $data['unit_id'], 'room_id' => $data['room_id'] ?? null];
                $previous = RoleAssignment::where($identity)->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()))->lockForUpdate()->get();
                abort_if($previous->contains(fn ($assignment) => $assignment->is_global || $assignment->can_administer), 422, 'Mandat khusus harus dicabut secara eksplisit sebelum diganti melalui formulir ini.');
                foreach ($previous as $assignment) {
                    $assignment->update(['ends_at' => now()]);
                }
                $assignment = RoleAssignment::create([...$identity, 'starts_at' => now(), 'ends_at' => $data['ends_at'] ?? null]);
                AuditEvent::record($assignment, 'assignment.granted', ['replaces' => $previous->pluck('id')->all(), 'reason' => $data['reason']], $assignment->unit_id);
            }
            if ($data['status'] !== 'active') {
                $locked->forceFill(['remember_token' => Str::random(60)])->save();
                DB::table('sessions')->where('user_id', $locked->id)->delete();
            }
            AuditEvent::record($locked, 'account.reviewed', ['status' => $data['status'], 'role' => $data['role'] ?? null, 'reason' => $data['reason']], $locked->profile?->unit_id);
        });

        try {
            $unitName = null;
            if (! empty($data['unit_id'])) {
                $unitName = OrganizationUnit::find($data['unit_id'])?->name;
            } elseif ($user->profile?->unit) {
                $unitName = $user->profile->unit->name;
            }

            $user->notify(new AccountStatusUpdated(
                status: $data['status'],
                reason: $data['reason'],
                role: $data['role'] ?? null,
                unitName: $unitName,
                updatedBy: $request->user()->name,
            ));
        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan notifikasi status akun: '.$e->getMessage(), [
                'user_id' => $user->id,
                'status' => $data['status'],
            ]);
        }

        return back()->with('success', 'Status akun dan penugasan tersimpan.');
    }

    public function updateAssignment(Request $request, RoleAssignment $assignment): RedirectResponse
    {
        abort_unless($this->scope->administer($request->user()), 403);
        abort_if($assignment->user_id === $request->user()->id, 403, 'Penugasan sendiri harus ditinjau pengelola lain.');
        $unitIds = $this->scope->administrativeUnitIds($request->user());
        abort_unless($this->scope->global($request->user()) || (! $assignment->is_global && ! $assignment->can_administer && in_array($assignment->unit_id, $unitIds)), 403);

        $data = $request->validate([
            'role' => ['required', Rule::in(['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'])],
            'unit_id' => ['nullable', Rule::in($unitIds)],
            'room_id' => ['nullable', Rule::in(Room::whereIn('unit_id', $unitIds)->pluck('id')->all())],
            'ends_at' => 'nullable|date',
            'reason' => 'required|string|max:5000',
            'current_password' => ($request->input('role') === 'Koordinator' || $assignment->role?->name === 'Koordinator') ? 'required|current_password' : 'nullable|current_password',
        ]);

        if ($data['role'] === 'Koordinator') {
            abort_unless($this->scope->global($request->user()), 403, 'Penugasan Koordinator memerlukan mandat admin global.');
        }
        if (! empty($data['role'])) {
            abort_unless(! empty($data['unit_id']), 422, 'Unit penugasan wajib dipilih.');
            if (! empty($data['room_id'])) {
                abort_unless(Room::whereKey($data['room_id'])->where('unit_id', $data['unit_id'])->exists(), 422);
            }
        }

        DB::transaction(function () use ($assignment, $data) {
            $locked = RoleAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->is_global || $locked->can_administer, 422, 'Mandat khusus tidak dapat diedit langsung melalui formulir ini.');

            $role = Role::firstOrCreate(['name' => $data['role']]);
            $before = $locked->only(['role_id', 'unit_id', 'room_id', 'starts_at', 'ends_at']);

            $locked->update([
                'role_id' => $role->id,
                'unit_id' => $data['unit_id'] ?? $locked->unit_id,
                'room_id' => $data['room_id'] ?? null,
                'ends_at' => $data['ends_at'] ?? null,
            ]);

            AuditEvent::record($locked, 'assignment.updated', ['before' => $before, 'reason' => $data['reason']], $locked->unit_id);
        });

        try {
            $user = User::find($assignment->user_id);
            if ($user) {
                $unitName = ! empty($data['unit_id']) ? OrganizationUnit::find($data['unit_id'])?->name : null;
                $roomName = ! empty($data['room_id']) ? Room::find($data['room_id'])?->name : null;
                $user->notify(new AssignmentUpdated(
                    action: 'updated',
                    roleName: $data['role'],
                    reason: $data['reason'],
                    unitName: $unitName,
                    roomName: $roomName,
                    endsAt: $data['ends_at'] ?? null,
                    updatedBy: $request->user()->name,
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan notifikasi pembaruan penugasan: '.$e->getMessage());
        }

        return back()->with('success', 'Penugasan berhasil diperbarui dan notifikasi telah dikirimkan.');
    }

    public function revokeAssignment(Request $request, RoleAssignment $assignment): RedirectResponse
    {
        abort_unless($this->scope->administer($request->user()), 403);
        abort_if($assignment->user_id === $request->user()->id, 403, 'Penugasan sendiri harus ditinjau pengelola lain.');
        $unitIds = $this->scope->administrativeUnitIds($request->user());
        abort_unless($this->scope->global($request->user()) || (! $assignment->is_global && ! $assignment->can_administer && in_array($assignment->unit_id, $unitIds)), 403);
        $request->validate(['reason' => 'required|string|max:5000', 'current_password' => 'required|current_password']);

        $assignment->load(['role', 'unit', 'room']);
        $roleName = $assignment->role?->name ?? 'Mandat';
        $unitName = $assignment->unit?->name;
        $roomName = $assignment->room?->name;

        DB::transaction(function () use ($request, $assignment) {
            $user = User::whereKey($assignment->user_id)->lockForUpdate()->firstOrFail();
            $locked = RoleAssignment::whereKey($assignment->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->ends_at && $locked->ends_at->lte(now()), 422, 'Penugasan sudah berakhir.');
            $before = $locked->only(['role_id', 'unit_id', 'room_id', 'starts_at', 'ends_at', 'is_global', 'can_administer']);
            $locked->update(['ends_at' => now()]);
            $user->forceFill(['remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $user->id)->delete();
            AuditEvent::record($locked, 'assignment.revoked', ['before' => $before, 'reason' => $request->input('reason')], $locked->unit_id);
        });

        try {
            $user = User::find($assignment->user_id);
            if ($user) {
                $user->notify(new AssignmentUpdated(
                    action: 'revoked',
                    roleName: $roleName,
                    reason: $request->input('reason'),
                    unitName: $unitName,
                    roomName: $roomName,
                    endsAt: now()->toDateString(),
                    updatedBy: $request->user()->name,
                ));
            }
        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan notifikasi pencabutan penugasan: '.$e->getMessage());
        }

        return back()->with('success', 'Mandat dicabut dan sesi akun diakhiri. Riwayat penugasan tetap tersimpan; petugas pengganti perlu ditetapkan untuk tugas terbuka.');
    }

    public function destroyUser(Request $request, User $user): RedirectResponse
    {
        $unitIds = $this->scope->administrativeUnitIds($request->user());
        abort_unless($this->scope->administer($request->user()) && ($this->scope->global($request->user()) || in_array($user->profile?->unit_id, $unitIds)), 403);
        abort_if($user->id === $request->user()->id, 403, 'Penghapusan akun sendiri tidak diizinkan melalui menu ini.');
        if (! $this->scope->global($request->user())) {
            abort_if($user->roleAssignments()->where(function ($query) use ($unitIds) {
                $query->where('is_global', true)->orWhereNotIn('unit_id', $unitIds)->orWhereNull('unit_id');
            })->exists(), 403, 'Akun lintas unit hanya dapat dihapus admin global.');
        }

        $data = $request->validate([
            'reason' => 'required|string|max:5000',
            'current_password' => 'required|current_password',
        ]);

        $userName = $user->name;
        $userEmail = $user->email;

        try {
            Notification::route('mail', $userEmail)->notify(new AccountDeleted(
                userName: $userName,
                userEmail: $userEmail,
                reason: $data['reason'],
                deletedBy: $request->user()->name,
            ));
        } catch (\Throwable $e) {
            Log::error('Gagal mengirimkan notifikasi penghapusan akun: '.$e->getMessage());
        }

        DB::transaction(function () use ($request, $user, $data) {
            AuditEvent::record($user, 'account.deleted', ['reason' => $data['reason'], 'name' => $user->name, 'email' => $user->email], $user->profile?->unit_id);

            DB::table('custody_assignments')->where('assigned_by', $user->id)->update(['assigned_by' => null]);
            DB::table('custody_assignments')->where('verified_by', $user->id)->update(['verified_by' => null]);
            DB::table('custody_assignments')->where('received_by', $user->id)->update(['received_by' => null]);
            DB::table('media')->where('uploaded_by', $user->id)->update(['uploaded_by' => null]);
            if (Schema::hasColumn('media', 'replaced_by')) {
                DB::table('media')->where('replaced_by', $user->id)->update(['replaced_by' => null]);
            }
            DB::table('audit_events')->where('user_id', $user->id)->update(['user_id' => null]);
            DB::table('role_assignments')->where('granted_by', $user->id)->update(['granted_by' => null]);
            DB::table('loan_items')->where('prepared_by', $user->id)->update(['prepared_by' => null]);
            DB::table('loan_items')->where('inspected_by', $user->id)->update(['inspected_by' => null]);
            DB::table('basts')->where('verified_by', $user->id)->update(['verified_by' => null]);
            DB::table('maintenance_logs')->where('inspected_by', $user->id)->update(['inspected_by' => null]);
            DB::table('spip_records')->where('assigned_to', $user->id)->update(['assigned_to' => null]);
            DB::table('spip_records')->where('reviewed_by', $user->id)->update(['reviewed_by' => null]);
            DB::table('work_records')->where('assigned_to', $user->id)->update(['assigned_to' => null]);
            DB::table('work_records')->where('created_by', $user->id)->update(['created_by' => $request->user()->id]);
            if (Schema::hasTable('asset_identifiers') && Schema::hasColumn('asset_identifiers', 'recorded_by')) {
                DB::table('asset_identifiers')->where('recorded_by', $user->id)->update(['recorded_by' => null]);
            }
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->delete();
        });

        return back()->with('success', 'Akun pengguna berhasil dihapus dan notifikasi telah dikirimkan ke email yang bersangkutan.');
    }

    public function organization(Request $request, string $kind): RedirectResponse
    {
        abort_unless($this->scope->administer($request->user()), 403);
        abort_unless(in_array($kind, ['unit', 'room', 'category']), 404);
        $data = $request->validate(['name' => 'required|string|max:255', 'code' => 'required|string|max:100', 'unit_id' => 'nullable|exists:organization_units,id']);
        if ($kind !== 'room') {
            abort_unless($this->scope->global($request->user()), 403);
        } else {
            abort_unless(in_array((int) ($data['unit_id'] ?? 0), $this->scope->administrativeUnitIds($request->user())), 403);
            abort_unless(! empty($data['unit_id']), 422);
        }
        DB::transaction(function () use ($kind, $data) {
            $record = match ($kind) {
                'unit' => OrganizationUnit::create(collect($data)->only(['name', 'code'])->all()),
                'category' => AssetCategory::create(collect($data)->only(['name', 'code'])->all()),
                default => Room::create($data),
            };
            AuditEvent::record($record, 'organization.'.$kind.'.created');
        });

        return back()->with('success', 'Data master ditambahkan.');
    }
}
