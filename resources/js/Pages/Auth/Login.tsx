import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { ShieldCheck, CheckCircle2, Clock, Shield } from 'lucide-react';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import FlashDialog from '@/Components/FlashDialog';

export default function Login({ status, canResetPassword }: { status?: string, canResetPassword?: boolean }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="min-h-screen flex font-sans text-gray-900 bg-white selection:bg-[#035b4f] selection:text-white">
            <Head title="Masuk" /><FlashDialog />

            {/* Left Side - Information */}
            <div className="hidden lg:flex lg:w-[55%] bg-white p-12 flex-col justify-between border-r border-gray-100 relative">
                <div>
                    <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-[#EBF2F0] text-[#035b4f] text-sm font-semibold mb-16">
                        <ShieldCheck className="w-4 h-4" />
                        Portal Layanan Barang Milik Negara Terpusat
                    </div>

                    <div className="max-w-xl">
                        <h1 className="text-[2.75rem] font-bold text-[#111827] leading-[1.15] tracking-tight mb-6">
                            Akses Administrasi &<br />Inventarisasi BMN Gakkum
                        </h1>
                        <p className="text-[1.05rem] text-gray-600 leading-relaxed mb-12 max-w-lg">
                            Kelola peminjaman sementara perangkat patroli, sarana laboratorium forensik,
                            verifikasi kondisi fisik ruangan, hingga penatausahaan dokumen BAST secara
                            transparan, tertib, dan akuntabel.
                        </p>

                        <div className="space-y-4">
                            {/* Feature 1 */}
                            <div className="flex gap-4 p-5 rounded-2xl bg-white border border-gray-100 shadow-sm">
                                <div className="mt-1 h-10 w-10 shrink-0 rounded-full bg-blue-50 flex items-center justify-center">
                                    <Shield className="w-5 h-5 text-blue-500" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-gray-900 mb-1">Otentikasi Berbasis Penugasan Resmi</h3>
                                    <p className="text-sm text-gray-600 leading-relaxed">
                                        Hak akses Pegawai, PJ Ruangan, dan Koordinator mengikuti penugasan serta cakupan unit yang dicatat administrator.
                                    </p>
                                </div>
                            </div>

                            {/* Feature 2 */}
                            <div className="flex gap-4 p-5 rounded-2xl bg-white border border-gray-100 shadow-sm">
                                <div className="mt-1 h-10 w-10 shrink-0 rounded-full bg-orange-50 flex items-center justify-center">
                                    <Clock className="w-5 h-5 text-orange-500" />
                                </div>
                                <div>
                                    <h3 className="font-bold text-gray-900 mb-1">Jalur Kerja Terpantau & Anti-Konflik</h3>
                                    <p className="text-sm text-gray-600 leading-relaxed">
                                        Pengajuan diverifikasi bertingkat, kepastian ketersediaan jadwal terpisah dari kondisi fisik
                                        barang.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div className="flex items-center gap-2 text-sm text-gray-500 font-medium mt-16">
                    <CheckCircle2 className="w-4 h-4 text-[#035b4f]" />
                    Verifikasi dua langkah untuk petugas dan jejak audit perubahan data.
                </div>
            </div>

            {/* Right Side - Form */}
            <div className="w-full lg:w-[45%] flex flex-col justify-between p-6 sm:p-12 relative bg-white">

                {/* Top header on right */}
                <div className="hidden sm:flex justify-end items-center gap-4 text-xs font-medium text-gray-500 w-full">
                    <span>Unit Kerja: Balai Jambi • Wilayah I Medan • Wilayah II Palembang</span>
                    <span className="px-3 py-1 bg-[#EBF2F0] text-[#035b4f] rounded-full font-bold">SIMON</span>
                </div>

                <div className="flex-1 flex items-center justify-center">
                    <div className="w-full max-w-[420px]">
                        {/* Box shadow card for login */}
                        <div className="bg-white rounded-[2rem] sm:border sm:border-gray-100 sm:shadow-[0_8px_30px_rgb(0,0,0,0.04)] sm:p-10">

                            <h2 className="text-2xl font-bold text-gray-900 mb-2">Masuk ke SIMON</h2>
                            <p className="text-sm text-gray-500 mb-8">Silakan gunakan NIP atau email dinas terdaftar.</p>

                            {status && <div className="mb-4 font-medium text-sm text-green-600">{status}</div>}

                            <form onSubmit={submit} className="space-y-5">
                                <div>
                                    <label htmlFor="email" className="block text-sm font-bold text-gray-700 mb-1.5">
                                        NIP atau Email Dinas <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="email"
                                        type="text"
                                        name="email"
                                        value={data.email}
                                        className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                        autoComplete="username"
                                        onChange={(e) => setData('email', e.target.value)}
                                        placeholder="NIP atau email terdaftar"
                                    />
                                    <p className="text-xs text-gray-500 mt-1.5">Gunakan NIP 18 digit atau alamat email akun Anda.</p>
                                    <InputError message={errors.email} className="mt-2" />
                                </div>

                                <div>
                                    <div className="flex justify-between items-center mb-1.5">
                                        <label htmlFor="password" className="block text-sm font-bold text-gray-700">
                                            Kata Sandi <span className="text-red-500">*</span>
                                        </label>
                                        {canResetPassword && (
                                            <Link href={route('password.request')} className="text-sm font-bold text-[#035b4f] hover:underline">
                                                Lupa kata sandi?
                                            </Link>
                                        )}
                                    </div>
                                    <div className="relative">
                                        <TextInput
                                            id="password"
                                            type="password"
                                            name="password"
                                            value={data.password}
                                            className="w-full px-4 py-3 rounded-xl border border-gray-200 focus:border-[#035b4f] focus:ring-1 focus:ring-[#035b4f] outline-none transition-colors"
                                            autoComplete="current-password"
                                            onChange={(e) => setData('password', e.target.value)}
                                            placeholder="Masukkan kata sandi"
                                        />
                                    </div>
                                    <InputError message={errors.password} className="mt-2" />
                                </div>

                                <div className="flex items-center">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <div className="relative flex items-center">
                                            <input
                                                type="checkbox"
                                                name="remember"
                                                checked={data.remember}
                                                onChange={(e) => setData('remember', e.target.checked)}
                                                className="peer w-5 h-5 border-2 border-gray-300 rounded text-[#035b4f] focus:ring-[#035b4f]"
                                            />
                                        </div>
                                        <span className="text-sm text-gray-600 font-medium">Ingat saya pada perangkat pribadi</span>
                                    </label>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-[#034f45] hover:bg-[#023b33] text-white font-bold py-3.5 px-4 rounded-xl flex items-center justify-center gap-2 transition-colors mt-2"
                                >
                                    Masuk ke Akun
                                    <span aria-hidden="true">&rarr;</span>
                                </button>
                            </form>

                            <div className="mt-10 relative">
                                <div className="absolute inset-0 flex items-center">
                                    <div className="w-full border-t border-gray-100"></div>
                                </div>
                                <div className="relative flex justify-center text-xs">
                                    <span className="bg-white px-4 text-gray-400 font-medium uppercase tracking-wider">Pegawai baru?</span>
                                </div>
                            </div>

                            <div className="mt-6 text-center space-y-4">
                                <p className="text-sm text-gray-500 font-medium">Belum memiliki akun terdaftar di Balai Gakkum Sumatera?</p>
                                <Link
                                    href={route('register')}
                                    className="block w-full bg-white border border-[#035b4f] text-[#035b4f] hover:bg-[#035b4f]/5 font-bold py-3 px-4 rounded-xl transition-colors"
                                >
                                    Registrasi Akun Pegawai
                                </Link>
                                <p className="text-[11px] text-gray-400 italic">
                                    *Setelah pendaftaran, akun memerlukan verifikasi email dan aktivasi oleh Koordinator TU.
                                </p>
                            </div>

                        </div>
                    </div>
                </div>

                {/* Footer on right */}
                <div className="w-full text-xs text-gray-400 font-medium flex flex-col sm:flex-row justify-between items-center gap-4 mt-8">
                    <span>© {new Date().getFullYear()} Balai Penegakan Hukum LHK Wilayah Sumatera. Seluruh hak cipta dilindungi.</span>
                    <div className="flex gap-4">
                        <a href="/help" className="hover:text-gray-600">Panduan Penggunaan</a>
                        <span>•</span>
                        <a href="/help" className="hover:text-gray-600">Kebijakan Privasi & BAST</a>
                        <span>•</span>
                        <a href="/help" className="hover:text-gray-600">Bantuan IT BMN</a>
                    </div>
                </div>
            </div>
        </div>
    );
}
