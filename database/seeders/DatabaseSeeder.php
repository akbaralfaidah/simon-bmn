<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Pastikan role sudah terbuat
        $roleKoordinator = Role::firstOrCreate(['name' => 'Koordinator BMN', 'description' => 'Akses penuh BMN']);
        $rolePegawai = Role::firstOrCreate(['name' => 'Pegawai', 'description' => 'Akses peminjam BMN']);

        // Akun Koordinator (Akbar Alfaidah)
        $akbar = User::updateOrCreate(
            ['email' => 'akbar@gakkum.id'],
            [
                'name' => 'Akbar Alfaidah',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        $akbar->roleAssignments()->firstOrCreate(['role_id' => $roleKoordinator->id]);

        // Akun Pegawai Biasa
        $budi = User::updateOrCreate(
            ['email' => 'budi@gakkum.id'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );
        $budi->roleAssignments()->firstOrCreate(['role_id' => $rolePegawai->id]);
    }
}
