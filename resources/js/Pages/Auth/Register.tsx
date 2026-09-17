import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { UserPlus, Eye, Check, ShieldCheck } from 'lucide-react';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import FlashDialog from '@/Components/FlashDialog';

export default function Register({ units }: { units: { id: number; name: string }[] }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        nip: '',
        unit_id: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <div className="min-h-screen flex font-sans text-gray-900 bg-white selection:bg-[#035b4f] selection:text-white">
            <Head title="Registrasi" /><FlashDialog />

            {/* Left Side - Information */}
            <div className="hidden lg:flex lg:w-[45%] bg-[#F7FAF9] p-12 flex-col justify-between border-r border-gray-100 relative">
                <div>
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#EBF2F0] text-[#035b4f] text-sm font-semibold mb-12">
                        <UserPlus className="w-4 h-4" />
                        Registrasi Akun Pegawai Baru
                    </div>

                    <div className="max-w-xl">
                        <h1 className="text-[2.25rem] font-bold text-[#111827] leading-[1.2] tracking-tight mb-6">
                            Pendaftaran Akun Terpadu<br />Pengelolaan BMN
                        </h1>
                        <p className="text-[1rem] text-gray-600 leading-relaxed mb-10 max-w-lg">
                            Setiap akun yang didaftarkan akan berstatus sebagai <strong>Pegawai</strong>.
                            Akses penugasan operasional seperti Penanggung Jawab Ruangan atau Koordinator BMN akan diberikan langsung oleh pejabat berwenang berdasarkan SK penugasan resmi.
                        </p>

                        <div className="p-6 rounded-2xl bg-white border border-gray-100 shadow-sm space-y-6">
                            <h3 className="text-xs font-bold text-gray-500 uppercase tracking-widest flex items-center gap-2">
                                <ClockIcon className="w-4 h-4" /> ALUR VERIFIKASI & AKTIVASI
                            </h3>

                            <div className="flex gap-4">
                                <div className="h-6 w-6 rounded-full bg-[#EBF2F0] text-[#035b4f] font-bold flex items-center justify-center shrink-0 text-xs">1</div>
                                <div>
                                    <h4 className="font-bold text-gray-900 mb-1 text-sm">Isi Formulir Lengkap</h4>
                                    <p className="text-xs text-gray-500 leading-relaxed">
                                        Gunakan email dinas aktif dan cantumkan NIP resmi bila telah berstatus ASN/PPPK.
                                    </p>
                                </div>
                            </div>

                            <div className="flex gap-4">
                                <div className="h-6 w-6 rounded-full bg-[#EBF2F0] text-[#035b4f] font-bold flex items-center justify-center shrink-0 text-xs">2</div>
                                <div>
                                    <h4 className="font-bold text-gray-900 mb-1 text-sm">Verifikasi Tautan Email</h4>
                                    <p className="text-xs text-gray-500 leading-relaxed">
                                        Sistem mengirim konfirmasi ke kotak masuk dinas untuk validasi kepemilikan.
                                    </p>
                                </div>
                            </div>

                            <div className="flex gap-4">
                                <div className="h-6 w-6 rounded-full bg-[#EBF2F0] text-[#035b4f] font-bold flex items-center justify-center shrink-0 text-xs">3</div>
                                <div>
                                    <h4 className="font-bold text-gray-900 mb-1 text-sm">Aktivasi oleh Petugas / Koordinator TU</h4>
                                    <p className="text-xs text-gray-500 leading-relaxed">
                                        Petugas memeriksa unit kerja dan menerbitkan izin peminjaman BMN.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-start gap-3 text-sm text-gray-500 font-medium mt-10 p-5 rounded-xl border border-gray-100 bg-white shadow-sm">
                    <ShieldCheck className="w-5 h-5 text-[#035b4f] shrink-0 mt-0.5" />
                    <p className="leading-relaxed">Mendukung pengelola password, sistem isi-otomatis (*autofill*), serta tempel kata sandi (*paste*) untuk kenyamanan keamanan.</p>
                </div>
            </div>

            {/* Right Side - Form */}
            <div className="w-full lg:w-[55%] flex flex-col justify-between p-6 sm:p-10 relative bg-white overflow-y-auto">

                {/* Top header on right */}
                <div className="hidden sm:flex justify-end items-center gap-4 text-[11px] font-medium text-gray-400 w-full mb-8">
                    <span>Unit Kerja: Balai Jambi • Wilayah I Medan • Wilayah II Palembang</span>
                    <span className="px-2.5 py-1 bg-[#EBF2F0] text-[#035b4f] rounded-full font-bold">Portal Resmi Pendaftaran</span>
                </div>

                <div className="flex-1 flex justify-center">
                    <div className="w-full max-w-[500px]">

                        <h2 className="text-2xl font-bold text-gray-900 mb-1">Daftar Akun Pegawai</h2>
                        <p className="text-sm text-gray-500 mb-8">Lengkapi data identitas penugasan Anda untuk mengajukan akun BMN.</p>

                        <form onSubmit={submit} className="space-y-5">

                            {/* Nama */}
                            <div>
                                <label htmlFor="name" className="block text-sm font-bold text-gray-700 mb-1.5">
                                    Nama Lengkap & Gelar <span className="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value={data.name}
                                    className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                    autoComplete="name"
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Nabila Putri, S.Hut."
                                />
                                <p className="text-[11px] text-gray-400 mt-1.5">Sesuai nama pada SK penugasan atau kartu pegawai.</p>
                                <InputError message={errors.name} className="mt-2" />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                                {/* Email */}
                                <div>
                                    <label htmlFor="email" className="block text-sm font-bold text-gray-700 mb-1.5">
                                        Email Dinas / Instansi <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value={data.email}
                                        className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                        autoComplete="email"
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="nabila.putri@example.test"
                                    />
                                    <p className="text-[11px] text-gray-400 mt-1.5">Gunakan domain dinas resmi yang aktif.</p>
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                {/* NIP */}
                                <div>
                                    <label htmlFor="nip" className="block text-sm font-bold text-gray-700 mb-1.5">
                                        NIP <span className="text-gray-400 font-normal">(Bila ada)</span>
                                    </label>
                                    <input
                                        id="nip"
                                        type="text"
                                        name="nip"
                                        value={data.nip}
                                        className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                        onChange={(e) => setData('nip', e.target.value)}
                                        placeholder="199408122020122003"
                                    />
                                    <p className="text-[11px] text-gray-400 mt-1.5">Kosongkan jika staf honorer / pramubakti lapangan.</p>
                                </div>
                            </div>

                            {/* Unit Kerja */}
                            <div>
                                <label htmlFor="unit_id" className="block text-sm font-bold text-gray-700 mb-1.5">
                                    Pilihan Unit Kerja Penugasan <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="unit_id"
                                    className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors appearance-none bg-white"
                                    value={data.unit_id}
                                    onChange={(e) => setData('unit_id', e.target.value)}
                                >
                                    <option value="">Pilih unit kerja</option>
                                    {units.map(unit => <option key={unit.id} value={unit.id}>{unit.name}</option>)}
                                </select>
                                <p className="text-[11px] text-gray-400 mt-1.5">Unit kerja menentukan katalog barang dan koordinator pemeriksa pengajuan.</p>
                                <InputError message={errors.unit_id} className="mt-2" />
                            </div>

                            {/* Password */}
                            <div>
                                <div className="flex justify-between items-center mb-1.5">
                                    <label htmlFor="password" className="block text-sm font-bold text-gray-700">
                                        Kata Sandi <span className="text-red-500">*</span>
                                    </label>
                                    <span className="text-[11px] text-gray-400">Dianjurkan frasa sandi (passphrase)</span>
                                </div>
                                <div className="relative">
                                    <TextInput
                                        id="password"
                                        type="password"
                                        name="password"
                                        value={data.password}
                                        className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                        autoComplete="new-password"
                                        onChange={(e) => setData('password', e.target.value)}
                                        placeholder="••••••••••••••••"
                                    />
                                </div>
                                {data.password.length >= 15 && (
                                    <div className="flex items-center gap-1.5 mt-2 bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-lg text-xs font-medium border border-emerald-100">
                                        <Check className="w-3.5 h-3.5" /> Panjang memenuhi standar keamanan (minimum 15 karakter).
                                    </div>
                                )}
                                <InputError message={errors.password} className="mt-2" />
                            </div>

                            {/* Confirm Password */}
                            <div>
                                <label htmlFor="password_confirmation" className="block text-sm font-bold text-gray-700 mb-1.5">
                                    Konfirmasi Kata Sandi <span className="text-red-500">*</span>
                                </label>
                                <div className="relative">
                                    <TextInput
                                        id="password_confirmation"
                                        type="password"
                                        name="password_confirmation"
                                        value={data.password_confirmation}
                                        className="w-full px-4 py-2.5 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                        autoComplete="new-password"
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                        placeholder="••••••••••••••••"
                                    />
                                </div>
                                {data.password_confirmation.length > 0 && data.password === data.password_confirmation && (
                                    <div className="flex items-center gap-1.5 mt-2 text-emerald-600 text-[11px] font-bold">
                                        <Check className="w-3.5 h-3.5" /> Kata sandi konfirmasi cocok
                                    </div>
                                )}
                                <InputError message={errors.password_confirmation} className="mt-2" />
                            </div>

                            {/* Terms */}
                            <div className="flex items-start mt-6 bg-gray-50 p-4 rounded-xl border border-gray-100">
                                <div className="flex items-center h-5 mt-0.5">
                                    <input
                                        id="terms"
                                        type="checkbox"
                                        required
                                        className="w-4 h-4 border-2 border-gray-300 rounded text-[#035b4f] focus:ring-[#035b4f] cursor-pointer"
                                    />
                                </div>
                                <label htmlFor="terms" className="ml-3 text-[11px] text-gray-600 leading-relaxed cursor-pointer font-medium">
                                    Saya menyatakan data di atas benar untuk penugasan BMN Balai Gakkum LHK Wilayah Sumatera dan bersedia mematuhi tata tertib peminjaman barang dinas.
                                </label>
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-[#034f45] hover:bg-[#023b33] text-white font-bold py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-colors mt-6"
                            >
                                Daftar Akun Pegawai
                                <span aria-hidden="true">&rarr;</span>
                            </button>

                            <div className="text-center mt-6">
                                <span className="text-sm text-gray-500 font-medium">Sudah memiliki akun terdaftar? </span>
                                <Link
                                    href={route('login')}
                                    className="text-sm font-bold text-[#035b4f] hover:underline"
                                >
                                    Masuk ke SIMON
                                </Link>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Footer on right */}
                <div className="w-full text-[11px] text-gray-400 font-medium flex flex-col sm:flex-row justify-between items-center gap-4 mt-12 border-t border-gray-50 pt-6">
                    <span>© {new Date().getFullYear()} Balai Penegakan Hukum LHK Wilayah Sumatera. Seluruh hak cipta dilindungi.</span>
                    <div className="flex gap-4">
                        <a href="/help" className="hover:text-gray-600">Panduan Registrasi BMN</a>
                        <span>•</span>
                        <a href="/help" className="hover:text-gray-600">Kebijakan Privasi & Data Dinas</a>
                        <span>•</span>
                        <a href="/help" className="hover:text-gray-600">Bantuan IT BMN Balai</a>
                    </div>
                </div>
            </div>
        </div>
    );
}

// Custom icon for the UI
function ClockIcon(props: any) {
    return (
        <svg
            {...props}
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
        >
            <circle cx="12" cy="12" r="10" />
            <polyline points="12 6 12 12 16 14" />
        </svg>
    );
}
