import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import StatusBadge from '@/Components/StatusBadge';
import { PageProps } from '@/types';

export default function Dashboard({ stats, recentLoans }: any) {
    const { auth } = usePage<PageProps>().props;
    return <AuthenticatedLayout header={<div><p className="mb-2 text-sm font-medium text-[#015850]">SIMON · Ringkasan aktivitas</p><h1 className="text-3xl font-bold">Selamat datang, {auth.user.name}</h1><p className="mt-2 text-slate-500">Data berikut mengikuti unit dan penugasan akun Anda.</p></div>}>
        <Head title="Beranda" />
        <section className="mb-8 rounded-2xl border border-[#015850]/15 bg-[#015850]/5 p-6 sm:p-8"><h2 className="text-xl font-bold">Apa yang ingin Anda lakukan?</h2><p className="mt-2 text-sm text-slate-600">Cari barang, ajukan peminjaman, atau lanjutkan pekerjaan yang menunggu.</p><div className="mt-5 flex flex-wrap gap-3"><Link className="simon-button" href={route('loans.create')}>Ajukan peminjaman</Link><Link className="simon-button-secondary" href={route('assets.index')}>Jelajahi katalog</Link>{auth.can.coordinate && <Link className="simon-button-secondary" href={route('loans.approvals')}>Periksa persetujuan ({stats.approvals})</Link>}</div></section>
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">{[['Aset dalam cakupan', stats.total], ['Kondisi baik', stats.kondisi_baik], ['Perlu perhatian', stats.kondisi_rusak], ['Pinjaman saya berjalan', stats.my_loans]].map(([label, value]) => <section className="simon-card" key={label}><p className="text-sm text-slate-500">{label}</p><p className="mt-3 text-3xl font-bold text-[#015850]">{value}</p></section>)}</div>
        <div className="mb-4 mt-9 flex justify-between"><h2 className="text-xl font-bold">Pengajuan terbaru saya</h2><Link href={route('loans.index')} className="text-sm font-semibold text-[#015850]">Lihat semua →</Link></div>
        <div className="space-y-3">{recentLoans.map((loan: any) => <Link key={loan.id} href={route('loans.show', loan.id)} className="simon-card flex flex-wrap justify-between gap-3"><span>#{loan.id} · {loan.purpose}</span><StatusBadge status={loan.status} /></Link>)}{!recentLoans.length && <p className="simon-card text-slate-500">Belum ada pengajuan. Mulai dengan memilih aset di katalog.</p>}</div>
    </AuthenticatedLayout>;
}
