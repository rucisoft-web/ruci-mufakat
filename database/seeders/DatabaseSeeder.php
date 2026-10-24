<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (filled(env('MUFAKAT_ADMIN_EMAIL')) && filled(env('MUFAKAT_ADMIN_PASSWORD'))) {
            User::updateOrCreate(['email' => env('MUFAKAT_ADMIN_EMAIL')], [
                'name' => 'Administrator', 'password' => env('MUFAKAT_ADMIN_PASSWORD'), 'role' => 'admin', 'is_active' => true,
            ]);
        }

        if (env('MUFAKAT_DEMO', false)) {
            $this->call(DemoSeeder::class);
        }
    }
}
