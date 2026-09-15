import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen flex font-sans">
            {/* Left Panel - Branding */}
            <div className="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-brand-primaryDark via-brand-primary to-brand-primaryLight relative overflow-hidden items-center justify-center">
                {/* Decorative Circles */}
                <div className="absolute -top-32 -left-32 w-96 h-96 bg-white/5 rounded-full blur-3xl" />
                <div className="absolute -bottom-24 -right-24 w-80 h-80 bg-brand-secondary/10 rounded-full blur-2xl" />
                <div className="absolute top-1/4 right-1/4 w-40 h-40 bg-white/5 rounded-full blur-xl" />

                <div className="relative z-10 text-center px-12 max-w-lg">
                    <div className="mx-auto mb-8 h-24 w-24 bg-white rounded-2xl flex items-center justify-center p-3 shadow-2xl shadow-black/20">
                        <img src="/images/logo-gakkum.webp" alt="Logo Gakkum" className="h-full w-full object-contain" />
                    </div>
                    <h1 className="text-4xl font-bold text-white tracking-tight mb-3">SIMON</h1>
                    <p className="text-lg text-brand-secondaryLight font-semibold uppercase tracking-widest mb-6">
                        Sistem Informasi Manajemen
                    </p>
                    <p className="text-white/70 text-sm leading-relaxed">
                        Kelola seluruh siklus hidup Barang Milik Negara — dari registrasi, peminjaman, penetapan,
                        hingga pelaporan inventarisasi — dalam satu platform terpusat untuk
                        Balai Penegakan Hukum Lingkungan Hidup dan Kehutanan Wilayah Sumatera.
                    </p>
                </div>
            </div>

            {/* Right Panel - Form */}
            <div className="w-full lg:w-1/2 flex flex-col items-center justify-center bg-[#F4F7F6] px-6 py-12">
                {/* Mobile Logo */}
                <div className="lg:hidden mb-8 text-center">
                    <Link href="/" className="inline-flex flex-col items-center gap-3">
                        <div className="h-16 w-16 bg-brand-primary rounded-xl flex items-center justify-center p-2 shadow-lg">
                            <img src="/images/logo-gakkum.webp" alt="Logo" className="h-full w-full object-contain" />
                        </div>
                        <div>
                            <span className="block text-2xl font-bold text-brand-primary tracking-tight">SIMON</span>
                            <span className="block text-[10px] text-brand-secondary font-semibold uppercase tracking-widest">Gakkum Sumatera</span>
                        </div>
                    </Link>
                </div>

                <div className="w-full max-w-md">
                    <div className="bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 px-8 py-10">
                        {children}
                    </div>
                    <p className="text-center text-xs text-gray-400 mt-6">
                        &copy; {new Date().getFullYear()} Balai Gakkum LHK Wilayah Sumatera. Hak cipta dilindungi.
                    </p>
                </div>
            </div>
        </div>
    );
}
