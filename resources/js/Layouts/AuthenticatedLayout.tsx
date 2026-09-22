import { Link, usePage, usePoll } from '@inertiajs/react';
import PageMotion from '@/Components/PageMotion';
import { PropsWithChildren, ReactNode } from 'react';
import { Dialog, DialogPanel, DialogTitle, Transition, TransitionChild } from '@headlessui/react';
import { useState } from 'react';
import { Bell, Menu, X, LogOut, Search, LayoutDashboard, Package, ArrowRightLeft, ShieldCheck, UserCheck, AlertTriangle, ClipboardCheck, Replace, Wrench, Trash2, Activity, FileText, BarChart3, History, FolderOpen, Lock, MonitorSmartphone, Import, Settings, Shield } from 'lucide-react';
import FlashDialog from '@/Components/FlashDialog';
import GlobalLoader from '@/Components/GlobalLoader';
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
        ...(operational ? [{ name: 'Persetujuan', url: route('loans.approvals'), icon: ShieldCheck }] : []),
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
        { name: 'Perangkat & sesi', url: route('sessions.index'), icon: MonitorSmartphone },
        ...(auth.can.coordinate ? [{ name: 'Impor aset', url: route('imports.index'), icon: Import }] : []),
        ...(auth.can.administer ? [{ name: 'Administrasi', url: route('administration.index'), icon: Settings }] : []),
    ];
    
    const current = usePage().url.split('?')[0];
    
    const navigation = (
        <div className="flex h-full flex-col justify-between bg-white">
            <div className="flex flex-col flex-1 overflow-y-auto">
                <div className="h-16 flex items-center px-6 bg-white shrink-0 border-b border-slate-100">
                    <img src="/images/logo-gakkum.webp" alt="Logo" className="h-8 w-auto object-contain" />
                    <div className="ml-3">
                        <span className="font-bold text-base tracking-tight text-slate-900 block leading-none">SIMON</span>
                        <span className="text-[10px] text-slate-500 tracking-wider block mt-1 uppercase font-semibold">Gakkum Sumatera</span>
                    </div>
                </div>
                
                <div className="px-3.5 py-3">
                    <div className="bg-gradient-to-br from-slate-50 via-slate-50 to-primary-50/20 border border-slate-200/90 rounded-xl p-3 shadow-2xs">
                        <div className="flex items-center justify-between mb-1">
                            <span className="text-[10px] uppercase tracking-wider text-slate-400 block font-semibold">Peran Fungsional</span>
                            <span className="w-1.5 h-1.5 rounded-full bg-accent"></span>
                        </div>
                        <div className="text-xs text-slate-800 font-semibold truncate">{auth.roles.join(' · ') || 'Pegawai'}</div>
                        <div className="text-[11px] text-slate-500 truncate mt-0.5">Akses sesuai SK penugasan</div>
                    </div>
                </div>
                
                <nav aria-label="Navigasi utama" className="flex-1 px-3 space-y-0.5 pb-6">
                    {nav.map(item => {
                        const isActive = new URL(item.url, window.location.origin).pathname === current;
                        const Icon = item.icon;
                        return (
                            <Link 
                                key={item.name} 
                                href={item.url} 
                                onClick={() => setOpen(false)} 
                                aria-current={isActive ? 'page' : undefined} 
                                className={`group flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-all duration-200 ease-[cubic-bezier(0.23,1,0.32,1)] ${
                                    isActive 
                                    ? 'bg-primary-50 text-primary font-semibold shadow-xs ring-1 ring-primary/20 border-l-[3px] border-primary translate-x-0.5' 
                                    : 'text-slate-600 font-medium hover:bg-slate-50 hover:text-slate-900 hover:translate-x-1 active:scale-[0.98]'
                                }`}
                            >
                                <Icon size={18} className={`transition-transform duration-200 group-hover:scale-110 ${isActive ? 'text-primary' : 'text-slate-400 group-hover:text-primary'}`} />
                                <span className="truncate">{item.name}</span>
                                {isActive && (
                                    <span className="ml-auto w-1.5 h-1.5 rounded-full bg-primary shrink-0"></span>
                                )}
                            </Link>
                        );
                    })}
                </nav>
            </div>
            
            <div className="p-3.5 border-t border-slate-100 bg-white shrink-0">
                <div className="flex items-center justify-between p-2.5 bg-slate-50/80 border border-slate-200/80 rounded-lg shadow-2xs">
                    <div className="flex items-center gap-2">
                        <span className="relative flex h-2 w-2">
                            <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                            <span className="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                        </span>
                        <span className="text-[11px] text-slate-700 font-medium">Sistem Operasional</span>
                    </div>
                    <span className="text-[10px] text-slate-400 font-mono">SIMAN v2.4</span>
                </div>
            </div>
        </div>
    );
    
    return (
        <div className="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
            <a href="#main-content" className="sr-only focus:not-sr-only focus:fixed focus:z-[70] focus:bg-white focus:p-4">Lewati ke konten</a>
            
            <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-200 lg:block z-50 bg-white shadow-xs">
                {navigation}
            </aside>
            
            <Transition show={open}>
                <Dialog onClose={setOpen} className="relative z-50 lg:hidden">
                    <TransitionChild
                        enter="transition-opacity ease-out duration-250"
                        enterFrom="opacity-0"
                        enterTo="opacity-100"
                        leave="transition-opacity ease-in duration-150"
                        leaveFrom="opacity-100"
                        leaveTo="opacity-0"
                    >
                        <div className="fixed inset-0 bg-slate-900/40 backdrop-blur-xs" />
                    </TransitionChild>

                    <div className="fixed inset-0 flex">
                        <TransitionChild
                            enter="transition ease-out duration-250 transform"
                            enterFrom="-translate-x-full"
                            enterTo="translate-x-0"
                            leave="transition ease-in duration-150 transform"
                            leaveFrom="translate-x-0"
                            leaveTo="-translate-x-full"
                        >
                            <DialogPanel className="relative flex w-72 max-w-[85vw] flex-1 flex-col bg-white shadow-xl">
                                <DialogTitle className="sr-only">Menu navigasi</DialogTitle>
                                <button 
                                    aria-label="Tutup menu" 
                                    className="absolute right-2 top-3 z-10 p-2.5 text-slate-500 hover:text-slate-900 active:scale-[0.98] transition-all duration-150 ease-out rounded-lg" 
                                    onClick={() => setOpen(false)}
                                >
                                    <X size={18} />
                                </button>
                                {navigation}
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </Dialog>
            </Transition>
            
            <div className="lg:pl-64">
                <header className="fixed top-0 left-0 lg:left-64 right-0 h-16 bg-white/95 backdrop-blur-md border-b border-slate-200 z-40 flex items-center justify-between px-4 sm:px-6 shadow-xs">
                    <div className="flex items-center gap-4 flex-1 max-w-2xl">
                        <button className="p-2 lg:hidden text-slate-600 hover:text-slate-900 hover:bg-slate-100 active:scale-[0.98] transition-all duration-150 ease-out rounded-lg" aria-label="Buka menu" onClick={() => setOpen(true)}>
                            <Menu size={20} />
                        </button>

                        <Link 
                            href={route('dashboard')} 
                            className="flex items-center gap-2 lg:hidden py-1 px-1.5 -ml-2 rounded-lg hover:bg-slate-100 active:scale-95 transition-all duration-150 ease-out"
                            aria-label="Kembali ke beranda"
                        >
                            <img src="/images/logo-gakkum.webp" alt="Logo Gakkum" className="h-7 w-auto object-contain" />
                            <div className="flex flex-col">
                                <span className="font-bold text-sm tracking-tight text-slate-900 leading-none">SIMON</span>
                                <span className="text-[9px] text-slate-500 tracking-wider uppercase font-semibold leading-tight">Gakkum</span>
                            </div>
                        </Link>
                        
                        <div className="hidden xl:flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200">
                            <Shield className="text-primary" size={15} />
                            <span className="text-xs text-slate-700 font-medium truncate">Balai Gakkum LH Wilayah Sumatera <span className="text-slate-400 font-normal">(Seksi I, II & Balai)</span></span>
                        </div>
                        
                        <form action={route('assets.index')} className="relative flex-1 max-w-sm hidden sm:block">
                            <label htmlFor="global-search" className="sr-only">Cari NUP, Dokumen, Aset, Lokasi...</label>
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" size={16} />
                            <input 
                                id="global-search" 
                                name="search" 
                                className="w-full h-9 pl-9 pr-4 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-800 placeholder:text-slate-400 focus:outline-none focus:bg-white focus:border-primary focus:ring-1 focus:ring-primary transition-all duration-150 ease-out" 
                                placeholder="Cari NUP, Dokumen, Aset, Lokasi..." 
                            />
                        </form>
                    </div>
                    
                    <div className="flex items-center gap-3 pl-4">
                        <Link 
                            href={route('workspace.index', 'notifications')} 
                            aria-label={'Notifikasi, ' + unreadNotifications + ' belum dibaca'} 
                            className="relative p-2 rounded-lg text-slate-500 hover:text-primary hover:bg-primary-50/60 active:scale-[0.98] transition-all duration-150 ease-out"
                        >
                            <Bell size={18} />
                            {unreadNotifications > 0 && (
                                <span className="absolute top-1.5 right-1.5 flex h-2 w-2">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-danger opacity-75"></span>
                                    <span className="relative inline-flex rounded-full h-2 w-2 bg-danger ring-2 ring-white"></span>
                                </span>
                            )}
                        </Link>
                        
                        <div className="h-6 w-px bg-slate-200 hidden sm:block"></div>
                        
                        <Link href={route('profile.edit')} className="flex items-center gap-2.5 pl-1 group cursor-pointer active:scale-[0.98] transition-all duration-150">
                            <div className="w-8 h-8 rounded-full bg-gradient-to-br from-primary-50 to-primary-100 text-primary flex items-center justify-center font-bold text-xs border border-primary/25 shadow-2xs group-hover:border-primary/40 group-hover:scale-105 transition-all">
                                {auth.user.name.charAt(0)}
                            </div>
                            <div className="hidden sm:flex flex-col text-left">
                                <span className="text-xs text-slate-800 leading-tight font-semibold group-hover:text-primary transition-colors">{auth.user.name}</span>
                                <span className="text-[10px] text-slate-400 leading-tight font-medium mt-0.5">Pengaturan Akun</span>
                            </div>
                        </Link>
                        
                        <Link 
                            href={route('logout')} 
                            method="post" 
                            as="button" 
                            className="p-2 text-slate-400 hover:text-danger hover:bg-danger-light active:scale-[0.98] rounded-lg transition-all duration-150 ease-out ml-1" 
                            aria-label="Keluar"
                        >
                            <LogOut size={18} />
                        </Link>
                    </div>
                </header>
                
                <main id="main-content" className="w-full pt-24 pb-12 min-h-screen px-4 sm:px-6 lg:px-8">
                    {header && <div className="mb-6">{header}</div>}
                    <PageMotion>{children}</PageMotion>
                </main>
            </div>
            <GlobalLoader />
            <FlashDialog />
        </div>
    );
}
