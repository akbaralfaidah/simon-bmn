import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import Pagination, { Paginated } from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';

export default function Index({ loans, approvalMode }: { loans: Paginated<any>; approvalMode: boolean }) {
    return <AuthenticatedLayout header={<div className="flex flex-wrap justify-between gap-4"><h1 className="text-2xl font-bold">{approvalMode ? 'Antrean persetujuan' : 'Peminjaman'}</h1><Link href={route('loans.create')} className="simon-button">Ajukan peminjaman</Link></div>}>
        <Head title={approvalMode ? 'Persetujuan' : 'Peminjaman'} />
        <p className="mb-6 text-slate-600">Pantau setiap tahap dari pengajuan hingga pengembalian fisik barang.</p>
        <div className="space-y-3">{loans.data.map(loan => <Link className="simon-card block hover:border-[#015850]" key={loan.id} href={route('loans.show', loan.id)}>
            <div className="flex flex-wrap justify-between gap-3"><h2 className="font-semibold">#{loan.id} · {loan.purpose}</h2><StatusBadge status={loan.status} /></div>
            <p className="mt-2 text-sm text-slate-500">{loan.user?.name} · {loan.start_date} — {loan.end_date} · {loan.items?.length} barang</p>
        </Link>)}</div>
        {!loans.data.length && <div className="simon-card text-center text-slate-500">Belum ada pengajuan pada daftar ini.</div>}
        <Pagination data={loans} />
    </AuthenticatedLayout>;
}
