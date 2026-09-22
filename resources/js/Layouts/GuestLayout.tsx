import { Link } from '@inertiajs/react';
import { PropsWithChildren } from 'react';
import FlashDialog from '@/Components/FlashDialog';
import GlobalLoader from '@/Components/GlobalLoader';

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="min-h-screen bg-slate-50/70 relative flex flex-col justify-between text-slate-800 font-sans overflow-x-hidden selection:bg-primary/20 selection:text-primary">
            {/* Subtle institutional ambient light */}
            <div className="absolute top-0 inset-x-0 h-96 bg-gradient-to-b from-primary-50/40 via-transparent to-transparent pointer-events-none -z-10" />
            <div className="absolute -top-24 -right-24 w-96 h-96 bg-accent-50/30 rounded-full blur-3xl pointer-events-none -z-10" />
            
            <FlashDialog />
            <GlobalLoader />
            
            {/* Top Official Header */}
            <header className="w-full border-b border-slate-200/80 bg-white/95 backdrop-blur-md py-3.5 px-6 sm:px-10 sticky top-0 z-30 shadow-2xs">
                <div className="max-w-5xl mx-auto flex items-center justify-between">
                    <Link href="/" className="flex items-center gap-3 group">
                        <img src="/images/logo-gakkum.webp" alt="Logo Gakkum" className="h-9 w-9 object-contain group-hover:scale-105 transition-transform" />
                        <div>
                            <div className="flex items-center gap-2">
                                <span className="text-base font-bold text-slate-900 tracking-tight group-hover:text-primary transition-colors">SIMON</span>
                                <span className="rounded-md bg-primary-50 px-2 py-0.5 text-[11px] font-semibold text-primary border border-primary-200/60">
                                    Wilayah Sumatera
                                </span>
                            </div>
                            <p className="text-[11px] text-slate-500">Balai Penegakan Hukum LH Wilayah Sumatera</p>
                        </div>
                    </Link>
                    <div className="hidden sm:flex items-center gap-2 text-xs text-slate-500 font-medium">
                        <span className="w-1.5 h-1.5 rounded-full bg-accent inline-block"></span>
                        Portal Resmi Penatausahaan BMN
                    </div>
                </div>
            </header>

            {/* Main Content Card */}
            <main className="flex-1 flex items-center justify-center px-4 py-10 sm:py-12">
                <div className="w-full max-w-md">
                    <div className="relative rounded-2xl border border-slate-200/90 bg-white p-7 sm:p-8 shadow-[0_8px_30px_rgb(0,0,0,0.06)] overflow-hidden transition-all duration-300">
                        {/* Top decorative institutional line */}
                        <div className="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-primary via-primary-600 to-accent" />
                        {children}
                    </div>
                </div>
            </main>

            {/* Official Footer */}
            <footer className="w-full border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500">
                <div className="max-w-5xl mx-auto flex flex-col sm:flex-row items-center justify-between gap-2">
                    <p>&copy; {new Date().getFullYear()} Balai Penegakan Hukum LH Wilayah Sumatera. Hak cipta dilindungi.</p>
                    <div className="flex items-center gap-4">
                        <Link href="/help" className="hover:text-primary transition-colors">Panduan Layanan</Link>
                        <span>•</span>
                        <Link href="/help" className="hover:text-primary transition-colors">Bantuan IT</Link>
                    </div>
                </div>
            </footer>
        </div>
    );
}
