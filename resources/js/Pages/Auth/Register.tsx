import { Head, Link, router, useForm } from '@inertiajs/react';
import { FormEventHandler, useState, useEffect } from 'react';
import InputError from '@/Components/InputError';
import FlashDialog from '@/Components/FlashDialog';
import GlobalLoader from '@/Components/GlobalLoader';
import Modal from '@/Components/Modal';
import { UserPlus, Info, ShieldCheck, Eye, EyeOff, AlertCircle, AlertTriangle, Check, X, Loader2, ArrowRight, CheckCircle2 } from 'lucide-react';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        nip: '',
        unit_kerja: '',
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [capsLockActive, setCapsLockActive] = useState(false);
    const [showSuccessModal, setShowSuccessModal] = useState(false);
    const [passwordFeedback, setPasswordFeedback] = useState<string | null>(null);

    const passwordRequirements = [
        { id: 'min_length', label: 'Minimal 8 karakter', test: (p: string) => p.length >= 8 },
        { id: 'has_number', label: 'Setidaknya 1 angka (0-9)', test: (p: string) => /[0-9]/.test(p) },
        { id: 'has_upper', label: 'Setidaknya 1 huruf besar (A-Z)', test: (p: string) => /[A-Z]/.test(p) },
        { id: 'has_lower', label: 'Setidaknya 1 huruf kecil (a-z)', test: (p: string) => /[a-z]/.test(p) },
        { id: 'has_symbol', label: 'Setidaknya 1 simbol (!@#$%^&* dll)', test: (p: string) => /[^A-Za-z0-9]/.test(p) },
    ];

    const isPasswordValid = passwordRequirements.every((req) => req.test(data.password));

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

        if (!isPasswordValid) {
            setPasswordFeedback('Password belum sesuai dengan ketentuan. Mohon penuhi seluruh kriteria di bawah.');
            return;
        }

        if (data.password !== data.password_confirmation) {
            setPasswordFeedback('Konfirmasi kata sandi tidak cocok dengan kata sandi.');
            return;
        }

        setPasswordFeedback(null);

        window.dispatchEvent(new CustomEvent('global-load-start', { detail: { message: 'Mendaftarkan akun...' } }));

        post(route('register'), {
            onSuccess: () => {
                setShowSuccessModal(true);
            },
            onError: () => {
                window.dispatchEvent(new CustomEvent('global-load-stop'));
            },
            onFinish: () => {
                window.dispatchEvent(new CustomEvent('global-load-stop'));
                reset('password', 'password_confirmation');
            },
        });
    };

    return (
        <div className="bg-slate-50 font-sans text-slate-800 min-h-screen flex flex-col justify-between">
            <Head title="Registrasi Pegawai - SIMON BMN Gakkum" />
            <FlashDialog />
            <GlobalLoader />

            {/* Top Header Banner */}
            <header className="w-full border-b border-slate-200 bg-white px-6 sm:px-10 py-3.5 sticky top-0 z-30">
                <div className="max-w-7xl mx-auto flex items-center justify-between">
                    <div className="flex items-center gap-3.5">
                        <img src="/images/logo-gakkum.webp" alt="Logo Gakkum" className="h-10 w-10 object-contain" />
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="font-bold text-lg tracking-tight text-slate-900">SIMON</span>
                                <span className="bg-primary-50 text-primary text-xs font-semibold px-2 py-0.5 rounded">Wilayah Sumatera</span>
                            </div>
                            <p className="text-xs text-slate-500">Balai Penegakan Hukum Lingkungan Hidup Wilayah Sumatera</p>
                        </div>
                    </div>
                    <div className="hidden md:flex items-center gap-4 text-xs text-slate-500">
                        <span>Unit Kerja: Balai Jambi · Wilayah I Medan · Wilayah II Palembang</span>
                    </div>
                </div>
            </header>

            {/* Main Container */}
            <main className="max-w-7xl mx-auto px-6 py-10 w-full flex-1 flex items-start justify-center">
                <div className="w-full grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">

                    {/* Sisi Kiri: Informasi Verifikasi & Alur Akun */}
                    <div className="lg:col-span-5 space-y-6 pt-2">
                        <div className="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-primary-50 text-primary text-xs font-medium border border-primary-100">
                            <UserPlus className="w-4 h-4 text-primary" />
                            Registrasi Akun Pegawai Baru
                        </div>

                        <div className="space-y-3">
                            <h1 className="text-3xl font-bold text-slate-900 tracking-tight leading-snug">
                                Pendaftaran Akun Pengelolaan BMN
                            </h1>
                            <p className="text-sm sm:text-base text-slate-600 leading-relaxed">
                                Setiap akun yang didaftarkan berstatus sebagai <strong className="text-slate-900 font-semibold">Pegawai</strong>. Akses penugasan operasional seperti Penanggung Jawab Ruangan atau Koordinator BMN akan diberikan oleh pejabat berwenang berdasarkan SK penugasan resmi.
                            </p>
                        </div>

                        {/* Tahapan Validasi Info Card */}
                        <div className="border border-slate-200 rounded-xl p-5 bg-white space-y-4 shadow-sm">
                            <h2 className="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <Info className="w-4 h-4 text-primary" />
                                Alur Pendaftaran & Aktivasi
                            </h2>

                            <div className="space-y-3">
                                <div className="flex items-start gap-3 text-sm">
                                    <span className="w-5 h-5 rounded-full bg-primary-50 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">1</span>
                                    <div>
                                        <p className="font-medium text-slate-900">Pengisian Data Penugasan</p>
                                        <p className="text-xs text-slate-500">Gunakan email dinas aktif dan cantumkan NIP resmi (ASN/PPPK) atau nama lengkap sesuai SK.</p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 text-sm">
                                    <span className="w-5 h-5 rounded-full bg-primary-50 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">2</span>
                                    <div>
                                        <p className="font-medium text-slate-900">Validasi Kepemilikan Email</p>
                                        <p className="text-xs text-slate-500">Sistem mengirimkan konfirmasi pendaftaran ke alamat email kedinasan Anda.</p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3 text-sm">
                                    <span className="w-5 h-5 rounded-full bg-primary-50 text-primary font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">3</span>
                                    <div>
                                        <p className="font-medium text-slate-900">Aktivasi oleh Petugas Tata Usaha</p>
                                        <p className="text-xs text-slate-500">Petugas memverifikasi kesesuaian unit kerja sebelum akun dapat mengajukan peminjaman BMN.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="text-xs text-slate-500 flex items-center gap-2 pt-1">
                            <ShieldCheck className="w-4 h-4 text-primary shrink-0" />
                            <span>Dukungan pengelola kata sandi dan autofill peramban diaktifkan.</span>
                        </div>
                    </div>

                    {/* Sisi Kanan: Formulir Registrasi */}
                    <div className="lg:col-span-7">
                        <div className="bg-white border border-slate-200 rounded-xl p-7 sm:p-9 shadow-sm">
                            <div className="mb-6">
                                <h2 className="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Formulir Pendaftaran</h2>
                                <p className="text-xs sm:text-sm text-slate-500 mt-1">Lengkapi data identitas penugasan Anda untuk mengajukan akun BMN.</p>
                            </div>

                            <form className="space-y-4" onSubmit={submit}>
                                {/* Nama Lengkap */}
                                <div>
                                    <label htmlFor="name" className="block text-sm font-medium text-slate-700 mb-1">
                                        Nama Lengkap & Gelar <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        name="name"
                                        autoComplete="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        className="w-full min-h-[40px] px-3.5 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                                        placeholder="Contoh: Nabila Putri, S.Hut."
                                    />
                                    <p className="text-xs text-slate-500 mt-1">Sesuai nama pada SK penugasan atau kartu pegawai.</p>
                                    <InputError message={errors.name} className="mt-1.5" />
                                </div>

                                {/* Grid Email & NIP */}
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    {/* Email Dinas */}
                                    <div>
                                        <label htmlFor="email" className="block text-sm font-medium text-slate-700 mb-1">
                                            Email Dinas / Instansi <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            type="email"
                                            id="email"
                                            name="email"
                                            autoComplete="username"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                            className="w-full min-h-[40px] px-3.5 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                                            placeholder="nama@example.test"
                                        />
                                        <InputError message={errors.email} className="mt-1.5" />
                                    </div>

                                    {/* NIP */}
                                    <div>
                                        <label htmlFor="nip" className="block text-sm font-medium text-slate-700 mb-1">
                                            NIP <span className="text-xs text-slate-500 font-normal">(Bila ada)</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="nip"
                                            name="nip"
                                            autoComplete="off"
                                            inputMode="numeric"
                                            value={data.nip}
                                            onChange={(e) => {
                                                const val = e.target.value;
                                                // Jangan biarkan browser mengisi email ke input NIP
                                                if (!val.includes('@')) {
                                                    setData('nip', val);
                                                }
                                            }}
                                            className="w-full min-h-[40px] px-3.5 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-primary focus:ring-1 focus:ring-primary transition-colors"
                                            placeholder="18 digit angka NIP"
                                        />
                                        <InputError message={errors.nip} className="mt-1.5" />
                                    </div>
                                </div>

                                {/* Pilihan Unit Kerja */}
                                <div>
                                    <label htmlFor="unit_kerja" className="block text-sm font-medium text-slate-700 mb-1">
                                        Unit Kerja Penugasan <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="unit_kerja"
                                        name="unit_kerja"
                                        value={data.unit_kerja}
                                        onChange={(e) => setData('unit_kerja', e.target.value)}
                                        required
                                        className="w-full min-h-[40px] px-3.5 py-2 border border-slate-300 rounded-lg text-sm text-slate-900 shadow-sm focus:border-primary focus:ring-1 focus:ring-primary bg-white transition-colors"
                                    >
                                        <option value="" disabled>Pilih Balai / Seksi Wilayah</option>
                                        <option value="1">Balai Gakkum LH Wilayah Sumatera - Kantor Balai Jambi</option>
                                        <option value="2">Seksi Wilayah I Medan</option>
                                        <option value="3">Seksi Wilayah II Palembang</option>
                                    </select>
                                    <p className="text-xs text-slate-500 mt-1">Menentukan lingkup katalog barang dan koordinator pemeriksa pengajuan.</p>
                                    <InputError message={errors.unit_kerja} className="mt-1.5" />
                                </div>

                                {/* Kolom Password */}
                                <div>
                                    <div className="flex items-center justify-between mb-1">
                                        <label htmlFor="password" className="text-sm font-medium text-slate-700">
                                            Kata Sandi <span className="text-red-500">*</span>
                                        </label>
                                        <span className="text-xs text-slate-500">Kombinasi kuat</span>
                                    </div>
                                    <div className="relative">
                                        <input
                                            type={showPassword ? 'text' : 'password'}
                                            id="password"
                                            name="password"
                                            autoComplete="new-password"
                                            value={data.password}
                                            onChange={(e) => {
                                                setData('password', e.target.value);
                                                if (passwordFeedback) setPasswordFeedback(null);
                                            }}
                                            required
                                            className={`w-full min-h-[40px] px-3.5 py-2 pr-11 border rounded-lg text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:outline-none transition-colors ${passwordFeedback || errors.password
                                                ? 'border-red-400 focus:border-red-500 focus:ring-1 focus:ring-red-500 bg-red-50/20'
                                                : 'border-slate-300 focus:border-primary focus:ring-1 focus:ring-primary'
                                                }`}
                                            placeholder="Masukkan kata sandi"
                                        />

                                        <button
                                            type="button"
                                            onClick={() => setShowPassword(!showPassword)}
                                            className="absolute right-0 inset-y-0 min-w-10 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none focus:text-primary active:scale-[0.98] transition-all duration-150 ease-out"
                                            aria-label="Tampilkan atau sembunyikan kata sandi"
                                        >
                                            {showPassword ? (
                                                <EyeOff className="w-4 h-4" />
                                            ) : (
                                                <Eye className="w-4 h-4" />
                                            )}
                                        </button>
                                    </div>

                                    {/* Notifikasi / Error Password belum sesuai ketentuan */}
                                    {(passwordFeedback || errors.password) && (
                                        <div className="mt-2 p-2.5 rounded-lg bg-red-50 border border-red-200 text-red-700 text-xs flex items-start gap-2 animate-in fade-in duration-150">
                                            <AlertCircle className="w-4 h-4 text-red-500 shrink-0 mt-0.5" />
                                            <span className="font-medium leading-relaxed">
                                                {passwordFeedback || errors.password}
                                            </span>
                                        </div>
                                    )}

                                    {capsLockActive && (
                                        <div className="flex items-center gap-1.5 text-xs text-amber-700 font-medium mt-1.5">
                                            <AlertTriangle className="w-3.5 h-3.5" />
                                            <span>Caps Lock sedang aktif</span>
                                        </div>
                                    )}

                                    {/* Checklist Ketentuan Password */}
                                    <div className="mt-2.5 p-3 rounded-lg border border-slate-200 bg-slate-50/80">
                                        <div className="flex items-center justify-between mb-2">
                                            <span className="text-xs font-semibold text-slate-700">Ketentuan Kata Sandi:</span>
                                            {data.password ? (
                                                <span className={`text-[11px] font-medium px-2 py-0.5 rounded-full ${isPasswordValid
                                                    ? 'bg-emerald-100 text-emerald-800'
                                                    : 'bg-amber-100 text-amber-800'
                                                    }`}>
                                                    {isPasswordValid ? '✓ Sesuai Ketentuan' : 'Belum Sesuai'}
                                                </span>
                                            ) : (
                                                <span className="text-[11px] text-slate-400">Wajib dipenuhi</span>
                                            )}
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                            {passwordRequirements.map((req) => {
                                                const passed = req.test(data.password);
                                                return (
                                                    <div
                                                        key={req.id}
                                                        className={`flex items-center gap-1.5 text-xs transition-colors duration-150 ${passed
                                                            ? 'text-emerald-700 font-medium'
                                                            : data.password
                                                                ? 'text-slate-500'
                                                                : 'text-slate-400'
                                                            }`}
                                                    >
                                                        {passed ? (
                                                            <Check className="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                                                        ) : (
                                                            <span className="w-3.5 h-3.5 rounded-full border border-slate-300 flex items-center justify-center shrink-0">
                                                                <span className="w-1 h-1 rounded-full bg-slate-300"></span>
                                                            </span>
                                                        )}
                                                        <span className="text-[11px]">{req.label}</span>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>

                                {/* Konfirmasi Kata Sandi */}
                                <div>
                                    <label htmlFor="password_confirmation" className="block text-sm font-medium text-slate-700 mb-1">
                                        Konfirmasi Kata Sandi <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <input
                                            type={showConfirmPassword ? 'text' : 'password'}
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            autoComplete="new-password"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            required
                                            className="w-full min-h-[40px] px-3.5 py-2 pr-11 border border-slate-300 rounded-lg text-sm text-slate-900 shadow-xs placeholder:text-slate-400 focus:border-primary focus:ring-2 focus:ring-primary/20 transition-all duration-150 ease-out"
                                            placeholder="Ulangi kata sandi"
                                        />

                                        <button
                                            type="button"
                                            onClick={() => setShowConfirmPassword(!showConfirmPassword)}
                                            className="absolute right-0 inset-y-0 min-w-10 flex items-center justify-center text-slate-400 hover:text-slate-600 focus:outline-none focus:text-primary active:scale-[0.98] transition-all duration-150 ease-out"
                                            aria-label="Tampilkan atau sembunyikan kata sandi"
                                        >
                                            {showConfirmPassword ? (
                                                <EyeOff className="w-4 h-4" />
                                            ) : (
                                                <Eye className="w-4 h-4" />
                                            )}
                                        </button>
                                    </div>
                                    <InputError message={errors.password_confirmation} className="mt-1.5" />
                                    {data.password && data.password_confirmation && data.password === data.password_confirmation && (
                                        <p className="text-xs text-emerald-600 mt-1 flex items-center gap-1 font-medium">
                                            <Check className="w-3.5 h-3.5" />
                                            Kata sandi cocok
                                        </p>
                                    )}
                                    {data.password && data.password_confirmation && data.password !== data.password_confirmation && (
                                        <p className="text-xs text-red-600 mt-1 flex items-center gap-1 font-medium">
                                            <X className="w-3.5 h-3.5" />
                                            Konfirmasi kata sandi belum cocok
                                        </p>
                                    )}
                                </div>

                                {/* Checkbox Kebijakan */}
                                <div className="pt-1">
                                    <label className="flex items-start gap-2.5 cursor-pointer">
                                        <input type="checkbox" required className="mt-0.5 h-4 w-4 rounded text-primary focus:ring-primary/25 border-slate-300 active:scale-[0.98] transition-all duration-150 ease-out" />
                                        <span className="text-xs text-slate-600 leading-relaxed">
                                            Saya menyatakan data di atas benar untuk penugasan BMN Balai Gakkum LH Wilayah Sumatera dan mematuhi tata tertib peminjaman barang dinas.
                                        </span>
                                    </label>
                                </div>

                                {/* Tombol Submit */}
                                <div className="pt-2 space-y-3">
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="group w-full min-h-[40px] bg-primary hover:bg-primary-700 active:scale-[0.98] text-white font-semibold px-4 py-2.5 rounded-lg transition-all duration-150 ease-out flex items-center justify-center gap-2 shadow-xs focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/25 disabled:cursor-not-allowed disabled:opacity-50 disabled:active:scale-100 text-sm"
                                    >
                                        {processing ? (
                                            <>
                                                <Loader2 className="animate-spin -ml-1 mr-2 h-4 w-4 text-white" />
                                                <span>Mendaftarkan Akun...</span>
                                            </>
                                        ) : (
                                            <>
                                                <span>Daftar Akun Pegawai</span>
                                                <ArrowRight className="w-4 h-4 transition-transform duration-150 group-hover:translate-x-0.5" />
                                            </>
                                        )}
                                    </button>

                                    <div className="text-center pt-1 text-sm text-slate-600">
                                        Sudah memiliki akun terdaftar?{' '}
                                        <Link href={route('login')} className="font-medium text-primary hover:underline">
                                            Masuk ke SIMON
                                        </Link>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </main>

            {/* Modal Sukses */}
            <Modal
                show={showSuccessModal}
                onClose={() => {
                    setShowSuccessModal(false);
                    router.visit(route('login'));
                }}
                maxWidth="md"
            >
                <div className="p-6 sm:p-7 text-center">
                    <div className="w-12 h-12 bg-primary-50 text-primary rounded-full flex items-center justify-center mx-auto mb-3">
                        <CheckCircle2 className="w-6 h-6" />
                    </div>

                    <span className="text-xs uppercase font-bold tracking-wider text-primary">Registrasi Berhasil</span>
                    <h3 className="text-lg font-bold text-slate-900 mt-1">Akun Menunggu Aktivasi</h3>

                    <p className="text-xs sm:text-sm text-slate-600 mt-2 leading-relaxed">
                        Data pendaftaran atas nama <strong className="text-slate-900">{data.name}</strong> telah dicatat ke pangkalan data unit kerja.
                    </p>

                    <div className="my-4 p-3.5 bg-slate-50 border border-slate-200 rounded-lg text-left text-xs space-y-2">
                        <div className="flex justify-between">
                            <span className="text-slate-500">Email:</span>
                            <span className="font-medium text-slate-900">{data.email}</span>
                        </div>
                        <div className="flex justify-between">
                            <span className="text-slate-500">Status Akun:</span>
                            <span className="text-amber-700 font-medium">Menunggu Aktivasi Tata Usaha</span>
                        </div>
                    </div>

                    <p className="text-xs text-slate-500">
                        Setelah diverifikasi oleh Koordinator Tata Usaha, akun Anda dapat langsung digunakan.
                    </p>

                    <div className="mt-5">
                        <button
                            type="button"
                            onClick={() => {
                                setShowSuccessModal(false);
                                router.visit(route('login'));
                            }}
                            className="simon-button w-full"
                        >
                            Kembali ke Halaman Login
                        </button>
                    </div>
                </div>
            </Modal>

            {/* Footer Standar */}
            <footer className="w-full border-t border-slate-200 bg-white px-6 sm:px-10 py-4 mt-8 text-xs text-slate-500">
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-3">
                    <p>© {new Date().getFullYear()} Balai Penegakan Hukum LH Wilayah Sumatera. Seluruh hak cipta dilindungi.</p>
                    <div className="flex items-center gap-4">
                        <Link href="/help" className="hover:text-primary transition-colors">Panduan Layanan</Link>
                        <span>•</span>
                        <Link href="/help" className="hover:text-primary transition-colors">Bantuan IT BMN</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
