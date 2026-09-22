import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import Pagination, { Paginated } from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Plus } from 'lucide-react';

export default function Index({ loans, approvalMode }: { loans: Paginated<any>; approvalMode: boolean }) {
    return (
        <AuthenticatedLayout 
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                            {approvalMode ? 'Antrean Persetujuan Peminjaman' : 'Daftar Peminjaman BMN'}
                        </h1>
                        <p className="mt-1 text-xs sm:text-sm text-slate-500">
                            Pantau setiap tahap dari draf pengajuan, verifikasi jadwal, serah terima BAST, hingga pengembalian.
                        </p>
                    </div>
                    <Link href={route('loans.create')} className="simon-button inline-flex items-center gap-1.5">
                        <Plus className="h-4 w-4" />
                        <span>Ajukan Peminjaman</span>
                    </Link>
                </div>
            }
        >
            <Head title={approvalMode ? 'Persetujuan Peminjaman' : 'Peminjaman BMN'} />
            
            <div className="space-y-3">
                {loans.data.map(loan => (
                    <Link 
                        className="simon-card block transition-all duration-150 ease-out hover:border-primary/50 hover:shadow-sm active:scale-[0.99]" 
                        key={loan.id} 
                        href={route('loans.show', loan.id)}
                    >
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                            <div className="flex items-center gap-2.5 min-w-0">
                                <span className="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded shrink-0">
                                    #{loan.id}
                                </span>
                                <h2 className="font-semibold text-slate-900 text-sm sm:text-base truncate">
                                    {loan.purpose}
                                </h2>
                            </div>
                            <div className="flex items-center gap-1.5 shrink-0 flex-wrap sm:justify-end">
                                <StatusBadge status={loan.status} type="loan" />
                            </div>
                        </div>
                        <div className="mt-2.5 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                            <span>Pemohon: <strong className="font-medium text-slate-700">{loan.user?.name}</strong></span>
                            <span>•</span>
                            <span>Periode: <strong className="font-medium text-slate-700">{loan.start_date} s.d. {loan.end_date}</strong></span>
                            <span>•</span>
                            <span>Jumlah: <strong className="font-medium text-slate-700">{loan.items?.length || 0} barang</strong></span>
                        </div>
                    </Link>
                ))}
            </div>

            {!loans.data.length && (
                <div className="simon-card text-center text-slate-500 p-12">
                    <p className="text-sm font-medium text-slate-700">Belum ada pengajuan peminjaman.</p>
                    <p className="mt-1 text-xs text-slate-400">Pengajuan yang Anda buat atau antrean persetujuan akan tampil di sini.</p>
                </div>
            )}

            <div className="mt-6">
                <Pagination data={loans} />
            </div>
        </AuthenticatedLayout>
    );
}
