<?php

namespace App\Console\Commands;

use App\Models\AuditEvent;
use App\Models\OrganizationUnit;
use App\Models\Role;
use App\Models\RoleAssignment;
use App\Models\User;
use App\Services\AccessScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('simon:setup {user : ID akun Koordinator yang sudah ada} {--global-admin : Berikan mandat admin seluruh unit secara eksplisit} {--units : Buat tiga unit awal sesuai rencana SIMON}')]
#[Description('Menyiapkan mandat awal tanpa membuat akun/password atau memindahkan aset')]
class SetupSimon extends Command
{
    public function handle(): int
    {
        if (! $this->option('global-admin')) {
            $this->error('Mandat global hanya dapat diberikan dengan opsi --global-admin yang disengaja.');

            return self::FAILURE;
        }
        $user = User::find($this->argument('user'));
        if (! $user || $user->status !== 'active' || ! $user->hasRole(AccessScope::COORDINATORS)) {
            $this->error('Akun harus sudah aktif dan memiliki role Koordinator.');

            return self::FAILURE;
        }
        DB::transaction(function () use ($user) {
            if ($this->option('units')) {
                foreach (['BALAI-JAMBI' => 'Balai Jambi', 'SW-I-MEDAN' => 'Seksi Wilayah I Medan', 'SW-II-PALEMBANG' => 'Seksi Wilayah II Palembang'] as $code => $name) {
                    OrganizationUnit::firstOrCreate(['code' => $code], ['name' => $name]);
                }
            }
            foreach (['Pegawai', 'Penanggung Jawab Ruangan'] as $name) {
                Role::firstOrCreate(['name' => $name]);
            }
            $assignment = app(AccessScope::class)->assignments($user, AccessScope::COORDINATORS)->whereNull('unit_id')->whereNull('room_id')->first();
            if (! $assignment) {
                $role = Role::firstOrCreate(['name' => 'Koordinator']);
                $assignment = RoleAssignment::create(['user_id' => $user->id, 'role_id' => $role->id]);
            }
            $assignment->update(['is_global' => true, 'can_administer' => true]);
            AuditEvent::record($assignment, 'admin.bootstrap_granted', ['user_id' => $user->id, 'global' => true]);
        });
        $this->info('Unit dan mandat awal siap. Password, verifikasi email, MFA, ruangan, dan penempatan aset tidak diubah.');

        return self::SUCCESS;
    }
}
