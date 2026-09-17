import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import { ShieldCheck, Shield, Clock, Lock, Eye, EyeOff, ArrowUp } from 'lucide-react';
import InputError from '@/Components/InputError';
import FlashDialog from '@/Components/FlashDialog';

export default function Login({ status, canResetPassword }: { status?: string, canResetPassword?: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
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
        <div className="min-h-screen bg-surface flex flex-col justify-between text-on-surface font-sans selection:bg-primary-container selection:text-on-primary">
            <Head title="Masuk" />
            <FlashDialog />

            {/* Header Instansi Resmi */}
            <header className="w-full border-b border-outline-variant bg-surface-container-lowest py-4 px-6 md:px-12 flex items-center justify-between z-10 relative shadow-[0_1px_8px_rgba(0,0,0,0.02)]">
                <div className="flex items-center gap-3">
                    <img src="/images/logo-gakkum.webp" alt="Logo SIMON" className="w-10 h-10 object-contain" />
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="font-bold text-lg text-primary tracking-tight">SIMON</span>
                            <span className="text-[11px] px-2 py-0.5 rounded bg-primary-container/10 text-primary font-bold uppercase tracking-wider">Wilayah Sumatera</span>
                        </div>
                        <p className="text-xs text-on-surface-variant">Balai Pengamanan & Penegakan Hukum LHK Wilayah Sumatera</p>
                    </div>
                </div>
                <div className="hidden sm:flex items-center gap-4 text-xs text-on-surface-variant font-medium">
                    <span>Unit Kerja: Balai Jambi • Wilayah I Medan • Wilayah II Palembang</span>
                    <span className="px-2.5 py-1 rounded-full border border-outline-variant text-primary font-semibold bg-primary-container/5">Sistem Resmi Terpadu</span>
                </div>
            </header>

            {/* Konten Utama: 2 Kolom */}
            <main className="flex-1 max-w-7xl w-full mx-auto px-6 py-8 md:py-12 grid grid-cols-1 lg:grid-cols-12 gap-12 items-center">
                
                {/* Kolom Kiri: Informasi BMN */}
                <div className="lg:col-span-6 space-y-6">
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-primary-container/10 text-primary text-xs font-semibold">
                        <ShieldCheck className="w-4 h-4" />
                        Portal Layanan Barang Milik Negara Terpusat
                    </div>

                    <h1 className="text-3xl sm:text-4xl lg:text-[2.5rem] font-bold text-on-surface leading-[1.15] tracking-tight">
                        Akses Administrasi &<br />Inventarisasi BMN Gakkum
                    </h1>

                    <p className="text-base text-on-surface-variant leading-relaxed max-w-lg">
                        Kelola peminjaman sementara perangkat patroli, sarana laboratorium forensik, verifikasi kondisi fisik ruangan, hingga penatausahaan dokumen BAST secara transparan, tertib, dan akuntabel.
                    </p>

                    <div className="space-y-3 pt-2 max-w-lg">
                        <div className="flex items-start gap-3 p-3.5 rounded-xl border border-outline-variant bg-surface-container-lowest shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
                            <div className="w-8 h-8 rounded-lg bg-info/10 flex items-center justify-center text-info shrink-0 mt-0.5">
                                <Shield className="w-4 h-4" />
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-on-surface">Otentikasi Berbasis Penugasan Resmi</h4>
                                <p className="text-xs text-on-surface-variant mt-0.5 leading-relaxed">Hak akses (Pegawai, PJ Ruangan, Koordinator) ditentukan otomatis dari SK penugasan yang tervalidasi.</p>
                            </div>
                        </div>

                        <div className="flex items-start gap-3 p-3.5 rounded-xl border border-outline-variant bg-surface-container-lowest shadow-[0_1px_2px_rgba(0,0,0,0.02)]">
                            <div className="w-8 h-8 rounded-lg bg-secondary-container/10 flex items-center justify-center text-secondary shrink-0 mt-0.5">
                                <Clock className="w-4 h-4" />
                            </div>
                            <div>
                                <h4 className="text-sm font-semibold text-on-surface">Jalur Kerja Terpantau & Anti-Konflik</h4>
                                <p className="text-xs text-on-surface-variant mt-0.5 leading-relaxed">Pengajuan diverifikasi bertingkat, kepastian ketersediaan jadwal terpisah dari kondisi fisik barang.</p>
                            </div>
                        </div>
                    </div>

                    <div className="text-xs text-on-surface-variant pt-2 border-t border-outline-variant flex items-center gap-2 max-w-lg">
                        <Lock className="w-4 h-4 text-primary shrink-0" />
                        <span>Mendukung pengamanan kata sandi ganda (2FA) & kepatuhan audit internal BPKP.</span>
                    </div>
                </div>

                {/* Kolom Kanan: Form Login */}
                <div className="lg:col-span-6 flex justify-center">
                    <div className="w-full max-w-[420px] bg-surface-container-lowest border border-outline-variant rounded-2xl p-6 sm:p-8 shadow-[0_4px_20px_-4px_rgba(30,25,53,0.08),_0_2px_8px_-2px_rgba(30,25,53,0.04)] relative">
                        
                        <div className="mb-6">
                            <h2 className="text-2xl font-bold text-on-surface">Masuk ke SIMON</h2>
                            <p className="text-sm text-on-surface-variant mt-1">Silakan gunakan NIP atau email dinas terdaftar.</p>
                        </div>

                        {status && <div className="mb-4 font-medium text-sm text-primary">{status}</div>}

                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <label htmlFor="email" className="block text-sm font-semibold text-on-surface mb-1.5">
                                    NIP atau Email Dinas <span className="text-error">*</span>
                                </label>
                                <input
                                    id="email"
                                    type="text"
                                    name="email"
                                    value={data.email}
                                    className="simon-input"
                                    autoComplete="username"
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="Contoh: 19940812... atau nama@example.test"
                                    required
                                />
                                <p className="text-xs text-on-surface-variant mt-1.5">Masukkan NIP 18 digit atau alamat email akun Anda.</p>
                                <InputError message={errors.email} className="mt-2" />
                            </div>

                            <div>
                                <div className="flex items-center justify-between mb-1.5">
                                    <label htmlFor="password" className="block text-sm font-semibold text-on-surface">
                                        Kata Sandi <span className="text-error">*</span>
                                    </label>
                                    {canResetPassword && (
                                        <Link href={route('password.request')} className="text-xs font-semibold text-primary hover:underline">
                                            Lupa kata sandi?
                                        </Link>
                                    )}
                                </div>
                                <div className="relative">
                                    <input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        name="password"
                                        value={data.password}
                                        className="simon-input pr-11"
                                        autoComplete="current-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="Masukkan kata sandi Anda"
                                        required
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3 top-1/2 -translate-y-1/2 p-1 text-on-surface-variant hover:text-primary focus:outline-none"
                                        aria-label="Tampilkan atau sembunyikan kata sandi"
                                    >
                                        {showPassword ? <EyeOff size={18} /> : <Eye size={18} />}
                                    </button>
                                </div>
                                
                                {capsLockActive && (
                                    <div className="text-xs text-secondary-container font-medium mt-1.5 flex items-center gap-1.5">
                                        <ArrowUp size={14} />
                                        <span>Caps Lock aktif</span>
                                    </div>
                                )}
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-between pt-1">
                                <label className="flex items-center gap-2.5 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="w-4 h-4 text-primary rounded border-outline-variant focus:ring-primary"
                                    />
                                    <span className="text-xs text-on-surface-variant font-medium select-none">Ingat perangkat ini selama 30 hari</span>
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="simon-button w-full mt-2"
                            >
                                <span>Masuk ke Akun</span>
                                <span aria-hidden="true" className="ml-1">&rarr;</span>
                            </button>
                        </form>

                        <div className="relative my-6">
                            <div className="absolute inset-0 flex items-center"><div className="w-full border-t border-outline-variant"></div></div>
                            <div className="relative flex justify-center text-xs"><span className="bg-surface-container-lowest px-3 text-on-surface-variant uppercase tracking-wider font-semibold">Pegawai baru?</span></div>
                        </div>

                        <div className="text-center space-y-3">
                            <p className="text-xs text-on-surface-variant font-medium">
                                Belum memiliki akun terdaftar di Balai Gakkum Sumatera?
                            </p>
                            <Link
                                href={route('register')}
                                className="inline-flex w-full min-h-[44px] items-center justify-center py-2.5 px-4 rounded-xl border border-outline hover:border-primary text-primary font-semibold text-sm transition-colors text-center"
                            >
                                Registrasi Akun Pegawai
                            </Link>
                            <p className="text-[11px] text-on-surface-variant italic">
                                *Setelah pendaftaran, akun memerlukan verifikasi email dan aktivasi oleh Koordinator TU.
                            </p>
                        </div>
                    </div>
                </div>
            </main>

            {/* Footer */}
            <footer className="w-full border-t border-outline-variant bg-surface-container-lowest py-4 px-6 md:px-12 flex flex-col sm:flex-row items-center justify-between text-xs text-on-surface-variant font-medium gap-3">
                <div>© {new Date().getFullYear()} Balai Penegakan Hukum LHK Wilayah Sumatera. Seluruh hak cipta dilindungi.</div>
                <div className="flex items-center gap-4">
                    <a href="/help" className="hover:text-primary transition-colors">Panduan Penggunaan</a>
                    <span className="text-outline-variant">•</span>
                    <a href="/help" className="hover:text-primary transition-colors">Kebijakan Privasi & BAST</a>
                    <span className="text-outline-variant">•</span>
                    <a href="/help" className="hover:text-primary transition-colors">Bantuan IT BMN</a>
                </div>
            </footer>
        </div>
    );
}
