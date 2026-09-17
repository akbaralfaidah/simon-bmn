import { Link, usePage, usePoll } from '@inertiajs/react';
import PageMotion from '@/Components/PageMotion';
import { PropsWithChildren, ReactNode } from 'react';
import { Dialog, DialogPanel, DialogTitle } from '@headlessui/react';
import { useState } from 'react';
import { Bell, Menu, X, LogOut, Search } from 'lucide-react';
import FlashDialog from '@/Components/FlashDialog';
import { PageProps } from '@/types';

export default function Authenticated({ header, children }: PropsWithChildren<{ header?: ReactNode }>) {
    const { auth, unreadNotifications } = usePage<PageProps>().props;
    usePoll(45000, { only: ['unreadNotifications'] });
    const [open, setOpen] = useState(false);
    const operational = auth.can.coordinate || auth.can.inspect;
    const nav = [
        { name: 'Beranda', url: route('dashboard') },
        { name: 'Katalog aset', url: route('assets.index') },
        { name: 'Peminjaman', url: route('loans.index') },
        ...(auth.can.coordinate ? [{ name: 'Persetujuan', url: route('loans.approvals') }] : []),
        { name: 'Penetapan pemegang', url: route('workspace.index', 'custody') },
        { name: 'Lapor kerusakan / kehilangan', url: route('workspace.index', 'incidents') },
        ...(operational ? [
            { name: 'Inventarisasi & DBR', url: route('workspace.index', 'inventory') },
            { name: 'Mutasi ruangan', url: route('workspace.index', 'transfers') },
            { name: 'Perawatan', url: route('workspace.index', 'maintenance') },
            { name: 'Penghapusan', url: route('workspace.index', 'disposals') },
            { name: 'SPIP', url: route('spip.index') },
            { name: 'Register ASP / PSP', url: route('workspace.index', 'registers') },
            { name: 'Laporan', url: route('workspace.index', 'reports') },
            { name: 'Jejak audit', url: route('workspace.index', 'audit') },
        ] : []),
        { name: 'Dokumen & arsip', url: route('documents.index') },
        { name: 'Keamanan & MFA', url: route('two-factor.show') },
        { name: 'Perangkat & sesi', url: route('sessions.index') },
        ...(auth.can.coordinate ? [{ name: 'Impor aset', url: route('imports.index') }] : []),
        ...(auth.can.administer ? [{ name: 'Administrasi', url: route('administration.index') }] : []),
    ];
    const current = usePage().url.split('?')[0];
    const navigation = <div className="flex h-full flex-col">
        <Link href={route('dashboard')} className="flex items-center gap-3 p-6"><img src="/images/logo-gakkum.webp" alt="" className="h-10 w-10 object-contain" /><span><strong className="text-lg tracking-wide text-[#015850]">SIMON</strong><span className="block text-[10px] text-slate-500">BARANG MILIK NEGARA</span></span></Link>
        <div className="mx-4 mb-4 rounded-xl bg-[#015850]/5 p-3 text-xs"><p className="font-semibold text-[#015850]">{auth.roles.join(' · ') || 'Pegawai'}</p><p className="mt-1 text-slate-500">Akses sesuai penugasan aktif</p></div>
        <nav aria-label="Navigasi utama" className="flex-1 space-y-1 overflow-y-auto px-3 pb-6">{nav.map(item => <Link key={item.name} href={item.url} onClick={() => setOpen(false)} aria-current={new URL(item.url, window.location.origin).pathname === current ? 'page' : undefined} className={'block rounded-xl px-4 py-3 text-sm font-medium ' + (new URL(item.url, window.location.origin).pathname === current ? 'bg-[#015850] text-white' : 'text-slate-600 hover:bg-slate-50')}>{item.name}</Link>)}</nav>
        <Link href={route('profile.edit')} className="border-t p-4 text-sm font-semibold">{auth.user.name}<span className="block text-xs font-normal text-slate-500">Profil & keamanan akun</span></Link>
    </div>;
    return <div className="min-h-screen bg-white text-[#1E1935]">
        <a href="#main-content" className="sr-only focus:not-sr-only focus:fixed focus:z-[70] focus:bg-white focus:p-4">Lewati ke konten</a>
        <aside className="fixed inset-y-0 left-0 hidden w-64 border-r border-slate-100 lg:block">{navigation}</aside>
        <Dialog open={open} onClose={setOpen} className="relative z-50 lg:hidden"><div className="fixed inset-0 bg-black/40" /><DialogPanel className="fixed inset-y-0 left-0 w-72 bg-white"><DialogTitle className="sr-only">Menu navigasi</DialogTitle><button aria-label="Tutup menu" className="absolute right-2 top-2 p-3" onClick={() => setOpen(false)}><X size={18} /></button>{navigation}</DialogPanel></Dialog>
        <div className="lg:pl-64">
            <header className="flex h-20 items-center justify-between gap-3 border-b border-slate-100 px-4 sm:px-8">
                <button className="p-3 lg:hidden" aria-label="Buka menu" onClick={() => setOpen(true)}><Menu /></button>
                <form action={route('assets.index')} className="relative w-full max-w-md"><label htmlFor="global-search" className="sr-only">Cari nama aset, kode, atau NUP</label><Search className="absolute left-3 top-3 text-slate-400" size={18} /><input id="global-search" name="search" className="simon-input pl-10" placeholder="Cari aset, kode, atau NUP…" /></form>
                <div className="flex items-center gap-2"><Link href={route('workspace.index', 'notifications')} aria-label={'Notifikasi, ' + unreadNotifications + ' belum dibaca'} className="relative p-3"><Bell size={20} />{unreadNotifications > 0 && <span className="absolute right-0 top-0 rounded-full bg-[#F77A04] px-1.5 text-xs font-bold text-[#1E1935]">{unreadNotifications}</span>}</Link><Link href={route('logout')} method="post" as="button" className="p-3" aria-label="Keluar"><LogOut size={20} /></Link></div>
            </header>
            <main id="main-content" className="mx-auto max-w-7xl px-4 py-7 sm:px-8">{header && <div className="mb-7">{header}</div>}<PageMotion>{children}</PageMotion></main>
        </div>
        <FlashDialog />
    </div>;
}
