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
use App\Services\AccessScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'users' => User::with(['profile', 'roleAssignments.role'])->when(! $this->scope->global($request->user()), fn ($query) => $query->whereHas('profile', fn ($query) => $query->whereIn('unit_id', (clone $units)->select('id'))))->latest()->paginate(20),
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
            'status' => 'required|in:active,suspended,pending', 'role' => ['nullable', Rule::in(['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'])],
            'unit_id' => ['nullable', Rule::in($unitIds)],
            'room_id' => ['nullable', Rule::in(Room::whereIn('unit_id', $unitIds)->pluck('id')->all())],
            'ends_at' => 'nullable|date|after:today', 'reason' => 'required|string|max:5000',
        ]);
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
        DB::transaction(function () use ($user, $data) {
            $locked = User::whereKey($user->id)->lockForUpdate()->firstOrFail();
            $locked->update(['status' => $data['status']]);
            if (! empty($data['unit_id'])) {
                EmployeeProfile::updateOrCreate(['user_id' => $locked->id], ['unit_id' => $data['unit_id']]);
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

        return back()->with('success', 'Status akun dan penugasan tersimpan.');
    }

    public function revokeAssignment(Request $request, RoleAssignment $assignment): RedirectResponse
    {
        abort_unless($this->scope->administer($request->user()), 403);
        abort_if($assignment->user_id === $request->user()->id, 403, 'Penugasan sendiri harus ditinjau pengelola lain.');
        $unitIds = $this->scope->administrativeUnitIds($request->user());
        abort_unless($this->scope->global($request->user()) || (! $assignment->is_global && ! $assignment->can_administer && in_array($assignment->unit_id, $unitIds)), 403);
        $request->validate(['reason' => 'required|string|max:5000', 'current_password' => 'required|current_password']);
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

        return back()->with('success', 'Mandat dicabut dan sesi akun diakhiri. Riwayat penugasan tetap tersimpan; petugas pengganti perlu ditetapkan untuk tugas terbuka.');
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
