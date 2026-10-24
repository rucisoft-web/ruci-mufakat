<?php

namespace Tests;

use App\Models\User;
use App\Support\Access;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

/**
 * Basis data uji (MySQL bm_test) diisi data demo sekali per proses, lalu setiap tes berjalan dalam transaksi.
 */
abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected static bool $seeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (! static::$seeded) {
            Artisan::call('migrate:fresh', ['--seed' => true, '--seeder' => 'Database\\Seeders\\DemoSeeder']);
            static::$seeded = true;
            $this->beginDatabaseTransaction();
        }

        Access::flush();
    }

    protected function demo(string $key): User
    {
        return User::where('email', $key.'@mufakat.demo')->firstOrFail();
    }
}
