import { Link, usePage, usePoll } from '@inertiajs/react';
import PageMotion from '@/Components/PageMotion';
import { PropsWithChildren, ReactNode } from 'react';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useState } from 'react';
import { Bell, Menu, X, LogOut, Search, LayoutDashboard, Package, ArrowRightLeft, ShieldCheck, UserCheck, AlertTriangle, ClipboardCheck, Replace, Wrench, Trash2, Activity, FileText, BarChart3, History, FolderOpen, Lock, MonitorSmartphone, Import, Settings, Shield } from 'lucide-react';
import FlashDialog from '@/Components/FlashDialog';
import { PageProps } from '@/types';

export default function Authenticated({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, unreadNotifications } = usePage<PageProps>().props;
    usePoll(45000, { only: ['unreadNotifications'] });
    const [open, setOpen] = useState(false);
    const operational = auth.can.coordinate || auth.can.inspect;
    
    const nav = [
        { name: 'Beranda', url: route('dashboard'), icon: LayoutDashboard },
        { name: 'Katalog aset', url: route('assets.index'), icon: Package },
        { name: 'Peminjaman', url: route('loans.index'), icon: ArrowRightLeft },
        ...(auth.can.coordinate ? [{ name: 'Persetujuan', url: route('loans.approvals'), icon: ShieldCheck }] : []),
        { name: 'Penetapan pemegang', url: route('workspace.index', 'custody'), icon: UserCheck },
        { name: 'Lapor kerusakan', url: route('workspace.index', 'incidents'), icon: AlertTriangle },
        ...(operational ? [
            { name: 'Inventarisasi & DBR', url: route('workspace.index', 'inventory'), icon: ClipboardCheck },
            { name: 'Mutasi ruangan', url: route('workspace.index', 'transfers'), icon: Replace },
            { name: 'Perawatan', url: route('workspace.index', 'maintenance'), icon: Wrench },
            { name: 'Penghapusan', url: route('workspace.index', 'disposals'), icon: Trash2 },
            { name: 'SPIP', url: route('spip.index'), icon: Activity },
            { name: 'Register ASP / PSP', url: route('workspace.index', 'registers'), icon: FileText },
            { name: 'Laporan', url: route('workspace.index', 'reports'), icon: BarChart3 },
            { name: 'Jejak audit', url: route('workspace.index', 'audit'), icon: History },
        ] : []),
        { name: 'Dokumen & arsip', url: route('documents.index'), icon: FolderOpen },
        { name: 'Keamanan & MFA', url: route('two-factor.show'), icon: Lock },
        { name: 'Perangkat & sesi', url: route('sessions.index'), icon: MonitorSmartphone },
        ...(auth.can.coordinate ? [{ name: 'Impor aset', url: route('imports.index'), icon: Import }] : []),
        ...(auth.can.administer ? [{ name: 'Administrasi', url: route('administration.index'), icon: Settings }] : []),
    ];
    
    const current = usePage().url.split('?')[0];
    
    const navigation = (
        <div className="flex h-full flex-col justify-between bg-surface-container-lowest">
            <div className="flex flex-col flex-1 overflow-y-auto">
                <div className="h-16 flex items-center px-6 bg-surface-container-lowest shrink-0">
                    <img src="/images/logo-gakkum.webp" alt="Logo" className="h-8 w-auto object-contain" />
                    <div className="ml-2">
                        <span className="font-bold text-lg tracking-tight text-primary block leading-none">SIMON</span>
                        <span className="text-[11px] text-outline tracking-wider block mt-1 uppercase font-medium">Gakkum Sumatera</span>
                    </div>
                </div>
                
                <div className="px-4 py-2">
                    <div className="bg-surface-container-low rounded-lg p-3">
                        <span className="text-[11px] uppercase tracking-wider text-outline block font-semibold mb-0.5">Peran Fungsional</span>
                        <div className="text-sm text-on-surface font-semibold truncate">{auth.roles.join(' · ') || 'Pegawai'}</div>
                        <div className="text-[13px] text-on-surface-variant truncate mt-0.5">Akses sesuai penugasan aktif</div>
                    </div>
                </div>
                
                <nav aria-label="Navigasi utama" className="flex-1 px-4 space-y-1 mt-1 pb-6">
                    {nav.map(item => {
                        const isActive = new URL(item.url, window.location.origin).pathname === current;
                        const Icon = item.icon;
                        return (
                            <Link 
                                key={item.name} 
                                href={item.url} 
                                onClick={() => setOpen(false)} 
                                aria-current={isActive ? 'page' : undefined} 
                                className={`flex items-center gap-3 px-4 py-2.5 rounded-lg text-sm transition-all ${
                                    isActive 
                                    ? 'bg-primary-container text-on-primary font-semibold shadow-sm' 
                                    : 'text-on-surface-variant font-medium hover:bg-surface-container-high hover:text-on-surface'
                                }`}
                            >
                                <Icon size={18} className={isActive ? 'text-on-primary' : 'text-on-surface-variant'} />
                                <span>{item.name}</span>
                            </Link>
                        );
                    })}
                </nav>
            </div>
            
            <div className="p-4 bg-surface-container-lowest shrink-0">
                <div className="flex items-center justify-between p-2 bg-surface-container-low rounded-lg">
                    <div className="flex items-center gap-2">
                        <span className="w-2 h-2 rounded-full bg-primary-container inline-block"></span>
                        <span className="text-xs text-outline font-medium">Sistem Terhubung</span>
                    </div>
                    <span className="text-xs text-outline">SIMAN v2.4</span>
                </div>
            </div>
        </div>
    );
    
    return (
        <div className="min-h-screen bg-surface font-sans text-on-surface antialiased">
            <a href="#main-content" className="sr-only focus:not-sr-only focus:fixed focus:z-[70] focus:bg-white focus:p-4">Lewati ke konten</a>
            
            <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-outline-variant lg:block shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-50">
                {navigation}
            </aside>
            
            <Dialog open={open} onClose={setOpen} className="relative z-50 lg:hidden">
                <div className="fixed inset-0 bg-black/40" />
                <DialogPanel className="fixed inset-y-0 left-0 w-72 bg-white">
                    <DialogTitle className="sr-only">Menu navigasi</DialogTitle>
                    <button aria-label="Tutup menu" className="absolute right-2 top-2 p-3 text-on-surface" onClick={() => setOpen(false)}>
                        <X size={18} />
                    </button>
                    {navigation}
                </DialogPanel>
            </Dialog>
            
            <div className="lg:pl-64">
                <header className="fixed top-0 left-0 lg:left-64 right-0 h-16 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)] z-40 flex items-center justify-between px-4 sm:px-6">
                    <div className="flex items-center gap-4 flex-1 max-w-2xl">
                        <button className="p-2 lg:hidden text-on-surface" aria-label="Buka menu" onClick={() => setOpen(true)}><Menu size={20} /></button>
                        
                        <div className="hidden lg:flex items-center gap-2 bg-surface-container-low px-4 py-1.5 rounded-lg border border-outline-variant/30">
                            <Shield className="text-primary-container" size={16} />
                            <span className="text-xs text-on-surface font-medium truncate">Balai Gakkum LHK Wilayah Sumatera <span className="text-outline font-normal">(Cakupan Otorisasi: Seksi I, II & Balai)</span></span>
                        </div>
                        
                        <form action={route('assets.index')} className="relative flex-1 max-w-md hidden sm:block">
                            <label htmlFor="global-search" className="sr-only">Cari NUP, No. Dokumen, Kode Aset, Lokasi...</label>
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-outline" size={18} />
                            <input id="global-search" name="search" className="w-full h-10 pl-10 pr-4 rounded-lg bg-surface-container-low border border-outline-variant/50 text-sm text-on-surface placeholder:text-outline focus:outline-none focus:bg-surface-container-lowest focus:border-primary focus:ring-1 focus:ring-primary transition-all" placeholder="Cari NUP, No. Dokumen, Kode Aset, Lokasi..." />
                        </form>
                    </div>
                    
                    <div className="flex items-center gap-4 pl-4">
                        <Link href={route('workspace.index', 'notifications')} aria-label={'Notifikasi, ' + unreadNotifications + ' belum dibaca'} className="relative p-2 rounded-lg text-outline hover:text-on-surface hover:bg-surface-container-low transition-colors">
                            <Bell size={20} />
                            {unreadNotifications > 0 && <span className="absolute top-1.5 right-1.5 w-2 h-2 bg-secondary-container rounded-full border-2 border-surface-container-lowest"></span>}
                        </Link>
                        
                        <div className="h-8 w-px bg-outline-variant/50 hidden sm:block"></div>
                        
                        <div className="flex items-center gap-3 pl-1 group cursor-pointer" onClick={() => window.location.href = route('profile.edit')}>
                            <div className="w-9 h-9 rounded-full bg-primary-container/10 text-primary-container flex items-center justify-center font-bold text-sm border border-primary-container/20">
                                {auth.user.name.charAt(0)}
                            </div>
                            <div className="hidden sm:flex flex-col text-left">
                                <span className="text-sm text-on-surface leading-tight font-semibold group-hover:text-primary transition-colors">{auth.user.name}</span>
                                <span className="text-xs text-outline leading-tight font-medium mt-0.5">Pengaturan Akun</span>
                            </div>
                        </div>
                        
                        <Link href={route('logout')} method="post" as="button" className="p-2 text-outline hover:text-error hover:bg-error-container/30 rounded-lg transition-colors" aria-label="Keluar">
                            <LogOut size={20} />
                        </Link>
                    </div>
                </header>
                
                <main id="main-content" className="w-full pt-20 min-h-screen px-4 sm:px-6 py-6 lg:px-8 lg:py-8">
                    {header && <div className="mb-7">{header}</div>}
                    <PageMotion>{children}</PageMotion>
                </main>
            </div>
            <FlashDialog />
        </div>
    );
}
