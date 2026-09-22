import GuestLayout from '@/Layouts/GuestLayout';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import ActionForm from '@/Components/ActionForm';
import OtpInput from '@/Components/OtpInput';
import { Head, Link, useForm } from '@inertiajs/react';
import { QRCodeSVG } from 'qrcode.react';
import { useState } from 'react';

export default function TwoFactor({ enabled, required, verified, qrUrl, recoveryCodes }: { enabled: boolean; required: boolean; verified: boolean; qrUrl: string | null; recoveryCodes: string[] }) {
    const [useRecovery, setUseRecovery] = useState(false);
    const form = useForm({ password: '', code: '', recovery_code: '' });

    return (
        <GuestLayout>
            <Head title="Verifikasi dua langkah" />
            <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Verifikasi Dua Langkah</h1>
            <p className="mb-6 mt-2 text-xs sm:text-sm text-slate-600">
                {required ? 'Wajib untuk PJ Ruangan dan Koordinator sebelum mengelola data BMN.' : 'Tambahkan perlindungan ekstra pada akun kedinasan Anda.'}
            </p>

            {!enabled && !qrUrl && (
                <form 
                    className="space-y-4" 
                    onSubmit={e => { 
                        e.preventDefault(); 
                        form.post(route('two-factor.enable'), { onFinish: () => form.reset('password') }); 
                    }}
                >
                    <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Siapkan aplikasi autentikator (Google Authenticator atau Microsoft Authenticator) pada perangkat dinas/pribadi. Masukkan kata sandi saat ini untuk menampilkan kode QR penyiapan.
                    </p>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-slate-700" htmlFor="setup-password">Kata sandi saat ini</label>
                        <TextInput 
                            id="setup-password" 
                            type="password" 
                            required 
                            autoComplete="current-password" 
                            value={form.data.password} 
                            onChange={e => form.setData('password', e.target.value)} 
                        />
                        <InputError message={form.errors.password} />
                    </div>
                    <button disabled={form.processing} className="simon-button w-full">
                        {form.processing ? 'Memproses...' : 'Mulai Penyiapan MFA'}
                    </button>
                </form>
            )}

            {!enabled && qrUrl && (
                <form 
                    className="space-y-5" 
                    onSubmit={e => { 
                        e.preventDefault(); 
                        form.post(route('two-factor.confirm'), { onFinish: () => form.reset('code') }); 
                    }}
                >
                    <p className="text-xs sm:text-sm text-slate-600 leading-relaxed">
                        Pindai QR ini dengan aplikasi autentikator Anda. Jangan membagikan tangkapan layar QR kepada siapa pun.
                    </p>
                    <div className="flex justify-center rounded-xl border border-slate-200 bg-white p-5 shadow-xs">
                        <QRCodeSVG value={qrUrl} size={190} marginSize={3} title="QR penyiapan autentikator" />
                    </div>
                    <div>
                        <label className="mb-2 block text-center text-xs font-medium text-slate-700" htmlFor="setup-code">
                            Kode 6 digit dari aplikasi autentikator
                        </label>
                        <OtpInput 
                            value={form.data.code} 
                            onChange={val => form.setData('code', val)} 
                            hasError={Boolean(form.errors.code)} 
                            disabled={form.processing} 
                        />
                        <InputError message={form.errors.code} className="text-center justify-center" />
                    </div>
                    <button className="simon-button w-full" disabled={form.processing || form.data.code.length < 6}>
                        {form.processing ? 'Memverifikasi...' : 'Konfirmasi & Aktifkan'}
                    </button>
                </form>
            )}

            {enabled && !verified && (
                <form 
                    className="space-y-5" 
                    onSubmit={e => { 
                        e.preventDefault(); 
                        form.transform(data => ({ 
                            ...data, 
                            code: useRecovery ? '' : data.code, 
                            recovery_code: useRecovery ? data.recovery_code : '' 
                        })); 
                        form.post(route('two-factor.challenge'), { 
                            onFinish: () => form.reset('code', 'recovery_code') 
                        }); 
                    }}
                >
                    <div>
                        <label className="mb-2 block text-center text-xs font-medium text-slate-700" htmlFor="mfa-code">
                            {useRecovery ? 'Kode pemulihan sekali pakai (8 karakter)' : 'Masukkan kode 6 digit dari autentikator'}
                        </label>
                        {useRecovery ? (
                            <TextInput 
                                id="mfa-code" 
                                type="text" 
                                autoComplete="one-time-code" 
                                placeholder="XXXX-XXXX" 
                                required 
                                value={form.data.recovery_code} 
                                onChange={e => form.setData('recovery_code', e.target.value)} 
                                className="text-center font-mono tracking-widest uppercase"
                            />
                        ) : (
                            <OtpInput 
                                value={form.data.code} 
                                onChange={val => form.setData('code', val)} 
                                hasError={Boolean(form.errors.code || form.errors.recovery_code)} 
                                disabled={form.processing} 
                            />
                        )}
                        <InputError message={form.errors.code || form.errors.recovery_code} className="text-center justify-center" />
                    </div>
                    
                    <button 
                        className="simon-button w-full" 
                        disabled={form.processing || (!useRecovery && form.data.code.length < 6) || (useRecovery && !form.data.recovery_code)}
                    >
                        {form.processing ? 'Memverifikasi...' : 'Verifikasi & Masuk'}
                    </button>
                    
                    <div className="text-center pt-1">
                        <button 
                            type="button" 
                            className="text-xs font-medium text-primary hover:underline transition-colors" 
                            onClick={() => { 
                                setUseRecovery(!useRecovery); 
                                form.clearErrors(); 
                            }}
                        >
                            {useRecovery ? '← Gunakan aplikasi autentikator' : 'Perangkat tidak tersedia? Gunakan kode pemulihan'}
                        </button>
                    </div>
                </form>
            )}

            {enabled && verified && (
                <div className="space-y-4">
                    <div className="rounded-lg bg-emerald-50 border border-emerald-200/80 p-3.5 text-xs text-emerald-800 flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span>MFA aktif dan sesi ini telah berhasil diverifikasi.</span>
                    </div>
                    {recoveryCodes.length > 0 && (
                        <section className="rounded-xl border border-slate-200 bg-white p-4">
                            <h2 className="font-semibold text-xs uppercase tracking-wider text-slate-700">Kode Pemulihan Cadangan</h2>
                            <p className="my-1.5 text-xs text-slate-500">
                                Setiap kode hanya dapat digunakan satu kali. Simpan di tempat yang aman.
                            </p>
                            <ul className="grid grid-cols-2 gap-2 rounded-lg bg-slate-50 border border-slate-200/60 p-3">
                                {recoveryCodes.map(code => (
                                    <li key={code} className="font-mono text-xs font-medium text-slate-800 tracking-wider text-center py-1 bg-white border border-slate-200 rounded">
                                        {code}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                    <ActionForm 
                        title="Buat ulang kode pemulihan" 
                        url={route('two-factor.recovery')} 
                        fields={[{ name: 'password', label: 'Kata sandi saat ini', type: 'password' }]} 
                        description="Seluruh kode pemulihan lama tidak akan berlaku lagi. Anda harus menyimpan rangkaian kode baru." 
                    />
                    {!required && (
                        <ActionForm 
                            title="Nonaktifkan MFA" 
                            url={route('two-factor.disable')} 
                            fields={[{ name: 'password', label: 'Kata sandi saat ini', type: 'password' }]} 
                        />
                    )}
                    <Link href={route('dashboard')} className="simon-button w-full">
                        Lanjut ke Beranda
                    </Link>
                </div>
            )}

            <div className="mt-6 pt-4 border-t border-slate-100 text-center">
                <Link href={route('logout')} method="post" as="button" className="text-xs text-slate-500 hover:text-red-600 transition-colors">
                    Keluar dari akun
                </Link>
            </div>
        </GuestLayout>
    );
}
