<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\User;
use App\Support\Audit;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Masuk sebagai tamu demo (hanya lihat) dan halaman publik manfaat bagi pimpinan. */
class DemoController
{
    /** URL tombol tamu; null bila fitur tamu mati atau data demo belum dimuat. */
    public static function guestUrl(): ?string
    {
        try {
            return config('mufakat.demo.guest') && User::where('email', User::DEMO_GUEST_EMAIL)->where('is_active', true)->exists()
                ? route('demo.tamu') : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public function guest(Request $request)
    {
        abort_unless(static::guestUrl(), 404);

        $guest = User::where('email', User::DEMO_GUEST_EMAIL)->firstOrFail();
        Auth::guard(Filament::getPanel('app')->getAuthGuard())->login($guest);
        $request->session()->regenerate();
        Audit::log('masuk_tamu', 'Masuk sebagai tamu demo dari '.$request->ip(), null, [], $guest->id);

        return redirect()->to(Filament::getPanel('app')->getUrl());
    }

    public function forLeaders()
    {
        return view('pimpinan', [
            'guestUrl' => static::guestUrl(),
            'demo' => rescue(fn () => Company::where('is_demo', true)->exists(), false, false),
        ]);
    }
}
