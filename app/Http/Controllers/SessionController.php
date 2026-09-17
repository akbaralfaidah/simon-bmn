<?php

namespace App\Http\Controllers;

use App\Models\AuditEvent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SessionController extends Controller
{
    public function index(Request $request): Response
    {
        $sessions = config('session.driver') === 'database' ? DB::table('sessions')->where('user_id', $request->user()->id)->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity'])->map(fn ($session) => ['current' => hash_equals($request->session()->getId(), $session->id), 'ip' => $session->ip_address, 'device' => mb_substr($session->user_agent ?? 'Perangkat tidak diketahui', 0, 250), 'last_active' => $session->last_activity]) : [];

        return Inertia::render('Auth/Sessions', ['sessions' => $sessions, 'supported' => config('session.driver') === 'database']);
    }

    public function revoke(Request $request): RedirectResponse
    {
        abort_unless(config('session.driver') === 'database', 422, 'Pencabutan sesi memerlukan penyimpanan sesi database.');
        $request->validate(['current_password' => 'required|current_password']);
        DB::transaction(function () use ($request) {
            DB::table('sessions')->where('user_id', $request->user()->id)->where('id', '!=', $request->session()->getId())->delete();
            $request->user()->forceFill(['remember_token' => Str::random(60)])->save();
            AuditEvent::record($request->user(), 'security.sessions_revoked');
        });
        $request->session()->regenerate();

        return back()->with('success', 'Sesi perangkat lain dan token ingat-saya lama telah dicabut.');
    }
}
