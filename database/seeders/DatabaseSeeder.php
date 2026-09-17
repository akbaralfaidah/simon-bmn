<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
        $this->command?->info('Role siap. Seeder tidak membuat akun, mereset password, memberikan mandat, atau mengimpor aset dinas.');
    }
}
