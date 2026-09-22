import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import InputError from '@/Components/InputError';
import FlashDialog from '@/Components/FlashDialog';

export default function Login({ status, canResetPassword }: { status?: string, canResetPassword?: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: true, // Default checked based on code.html
    });

    const [showPassword, setShowPassword] = useState(false);
    const [capsLockActive, setCapsLockActive] = useState(false);

    useEffect(() => {
        const handleKeyUp = (e: KeyboardEvent) => {
            if (e.getModifierState) {
                setCapsLockActive(e.getModifierState('CapsLock'));
            }
        };
        window.addEventListener('keyup', handleKeyUp);
        return () => window.removeEventListener('keyup', handleKeyUp);
    }, []);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen bg-slate-50 flex flex-col justify-between text-slate-800 font-sans">
            <Head title="Masuk - SIMON BMN Gakkum Wilayah Sumatera" />
            <FlashDialog />

            {/* Header Instansi Resmi */}
            <header className="w-full border-b border-slate-200 bg-white py-3.5 px-6 sm:px-10">
                <div className="max-w-7xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-3.5">
                        <img src="/images/logo-gakkum.webp" alt="Logo Gakkum" className="h-10 w-10 object-contain" />
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-lg font-bold tracking-tight text-slate-900">SIMON</span>
                                <span className="rounded bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary">
                                    Wilayah Sumatera
                                </span>
                            </div>
                            <p className="text-xs text-slate-500">Balai Pengamanan & Penegakan Hukum LH Wilayah Sumatera</p>
                        </div>
                    </div>
                    <div className="hidden md:flex items-center gap-4 text-xs text-slate-500">
                        <span>Unit Kerja: Balai Jambi · Wilayah I Medan · Wilayah II Palembang</span>
                    </div>
                </div>
            </header>

            {/* Konten Utama */}
            <main className="flex-1 max-w-7xl w-full mx-auto px-6 py-10 sm:py-16 flex items-center justify-center">
                <div className="w-full grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-16 items-center">
                    
                    {/* Kolom Kiri: Informasi Kelembagaan */}
                    <div className="lg:col-span-6 space-y-6">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-primary-50 text-primary text-xs font-medium border border-primary-100">
                            <svg className="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Sistem Informasi Manajemen BMN
                        </div>

                        <div className="space-y-3">
                            <h1 className="text-3xl sm:text-4xl font-bold text-slate-900 tracking-tight leading-tight">
                                Pengelolaan Aset & Inventarisasi Resmi Kedinasan
                            </h1>
                            <p className="text-slate-600 text-sm sm:text-base leading-relaxed">
                                Portal terpadu pencatatan, peminjaman operasional, dan penatausahaan Barang Milik Negara (BMN) di lingkungan Balai Penegakan Hukum LH Wilayah Sumatera.
                            </p>
                        </div>

                        {/* Poin Informasi Prosedural */}
                        <div className="space-y-3 pt-2 border-t border-slate-200">
                            <div className="flex items-start gap-3">
                                <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary-50 text-primary">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div className="text-sm text-slate-600">
                                    <span className="font-medium text-slate-900">Validasi Penugasan Resmi</span> — Akses pegawai, PJ Ruangan, dan Koordinator disesuaikan berdasarkan SK kedinasan.
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary-50 text-primary">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div className="text-sm text-slate-600">
                                    <span className="font-medium text-slate-900">Akuntabilitas Berita Acara (BAST)</span> — Seluruh riwayat peminjaman dan serah terima terdokumentasi dan terverifikasi.
                                </div>
                            </div>

                            <div className="flex items-start gap-3">
                                <div className="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded bg-primary-50 text-primary">
                                    <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div className="text-sm text-slate-600">
                                    <span className="font-medium text-slate-900">Integritas & Kerahasiaan Data</span> — Perlindungan data inventarisasi dan validasi digital dokumen BAST kedinasan.
                                </div>
                            </div>
                        </div>

                        <div className="pt-2 text-xs text-slate-500">
                            Kendala akses atau kebutuhan teknis sistem?{' '}
                            <a
                                href="mailto:akbaralfaidahohs@gmail.com?subject=%5BTICKET-SIMON%5D%20Kendala%20Akses%20dan%20Bantuan%20Sistem&body=Halo%20Developer%2C%0A%0ASaya%20mengalami%20kendala%20berikut%20pada%20SIMON%20BMN%3A%0ANama%3A%20%0ANIP%20%2F%20Unit%20Kerja%3A%20%0AKendala%3A%20%0A%0ATerima%20kasih."
                                className="font-medium text-primary hover:underline"
                            >
                                Hubungi Developer
                            </a>
                            .
                        </div>
                    </div>

                    {/* Kolom Kanan: Kotak Form Login */}
                    <div className="lg:col-span-6 flex justify-center lg:justify-end">
                        <div className="w-full max-w-md bg-white border border-slate-200 rounded-xl p-7 sm:p-9 shadow-sm">
                            <div className="mb-6">
                                <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Masuk ke SIMON</h2>
                                <p className="text-xs sm:text-sm text-slate-500 mt-1">Gunakan NIP atau alamat email dinas yang telah terdaftar.</p>
                            </div>

                            {status && (
                                <div className="mb-4 rounded-lg bg-primary-50 border border-primary-200 p-3 text-xs font-medium text-primary">
                                    {status}
                                </div>
                            )}

                            <form className="space-y-4" onSubmit={submit}>
                                {/* Input NIP / Email */}
                                <div>
                                    <label htmlFor="email" className="block text-sm font-medium text-slate-700 mb-1">
                                        NIP atau Email Dinas <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="email"
                                        name="email"
                                        value={data.email}
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="18 digit NIP atau nama@example.test"
                                        className={`w-full min-h-[40px] px-3.5 py-2 text-sm text-slate-900 rounded-lg shadow-xs placeholder:text-slate-400 transition-all duration-150 ease-out border focus:ring-2 ${
                                            errors.email
                                                ? 'border-red-400 bg-red-50/20 focus:border-red-500 focus:ring-red-200/50'
                                                : 'border-slate-300 bg-white focus:border-primary focus:ring-primary/20'
                                        }`}
                                        required
                                        autoComplete="username"
                                    />
                                    <InputError message={errors.email} className="mt-1.5" />
                                </div>

                                {/* Input Kata Sandi */}
                                <div>
                                    <div className="flex items-center justify-between mb-1">
                                        <label htmlFor="password" className="block text-sm font-medium text-slate-700">
                                            Kata Sandi <span className="text-red-500">*</span>
                                        </label>
                                        {canResetPassword && (
                                            <Link href={route('password.request')} className="text-xs font-medium text-primary hover:underline">
                                                Lupa kata sandi?
                                            </Link>
                                        )}
                                    </div>
                                    <div className="relative">
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            id="password"
                                            name="password"
                                            value={data.password}
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Masukkan kata sandi"
                                            className={`w-full min-h-[40px] px-3.5 py-2 pr-11 text-sm text-slate-900 rounded-lg shadow-xs placeholder:text-slate-400 transition-all duration-150 ease-out border focus:ring-2 ${
                                                errors.password
                                                    ? 'border-red-400 bg-red-50/20 focus:border-red-500 focus:ring-red-200/50'
                                                    : 'border-slate-300 bg-white focus:border-primary focus:ring-primary/20'
                                            }`}
                                            required
                                            autoComplete="current-password"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-0 inset-y-0 min-w-10 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none focus:text-primary active:scale-[0.98] transition-all duration-150 ease-out"
                                            aria-label="Tampilkan atau sembunyikan kata sandi"
                                        >
                                            {showPassword ? (
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                                </svg>
                                            ) : (
                                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </svg>
                                            )}
                                        </button>
                                    </div>
                                    {capsLockActive && (
                                        <div className="text-xs text-amber-700 font-medium mt-1 flex items-center gap-1.5">
                                            <svg className="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                            <span>Caps Lock sedang aktif</span>
                                        </div>
                                    )}
                                    <InputError message={errors.password} className="mt-1.5" />
                                </div>

                                {/* Ingat Perangkat */}
                                <div className="flex items-center justify-between pt-1">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="remember"
                                            checked={data.remember}
                                            onChange={(e) => setData('remember', e.target.checked)}
                                            className="h-4 w-4 text-primary rounded border-slate-300 focus:ring-primary/25 active:scale-[0.98] transition-all duration-150 ease-out"
                                        />
                                        <span className="text-xs text-slate-600 select-none">Ingat perangkat selama 30 hari</span>
                                    </label>
                                </div>

                                {/* Tombol Masuk Utama */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="group w-full min-h-[40px] py-2.5 px-4 rounded-lg bg-primary hover:bg-primary-700 active:scale-[0.98] text-white font-semibold text-sm transition-all duration-150 ease-out flex items-center justify-center gap-2 shadow-xs focus:outline-none focus:ring-2 focus:ring-primary/25 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100"
                                >
                                    {processing ? (
                                        <>
                                            <svg className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>Memverifikasi Akun...</span>
                                        </>
                                    ) : (
                                        <>
                                            <span>Masuk ke Akun</span>
                                            <svg className="w-4 h-4 transition-transform duration-150 group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                            </svg>
                                        </>
                                    )}
                                </button>
                            </form>

                            {/* Garis Pemisah */}
                            <div className="relative my-6">
                                <div className="absolute inset-0 flex items-center"><div className="w-full border-t border-slate-200"></div></div>
                                <div className="relative flex justify-center text-xs"><span className="bg-white px-2.5 text-slate-400">Pegawai baru?</span></div>
                            </div>

                            {/* Tombol Registrasi Akun Pegawai */}
                            <div className="text-center space-y-2">
                                <Link
                                    href={route('register')}
                                    className="inline-flex items-center justify-center w-full min-h-[40px] py-2 px-4 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 active:scale-[0.98] text-slate-700 font-medium text-sm transition-all duration-150 ease-out shadow-xs"
                                >
                                    Registrasi Akun Pegawai
                                </Link>
                                <p className="text-[11px] text-slate-500">
                                    Setelah pendaftaran, akun akan diaktivasi oleh Petugas Tata Usaha.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            {/* Footer Standar BMN */}
            <footer className="w-full border-t border-slate-200 bg-white py-4 px-6 sm:px-10 text-xs text-slate-500">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div>© {new Date().getFullYear()} Balai Penegakan Hukum LH Wilayah Sumatera. Seluruh hak cipta dilindungi.</div>
                    <div className="flex items-center gap-4">
                        <a href="/help" className="hover:text-primary transition-colors">Panduan Layanan</a>
                        <span>•</span>
                        <a href="/help" className="hover:text-primary transition-colors">Bantuan IT BMN</a>
                    </div>
                </div>
            </footer>
        </div>
    );
}
