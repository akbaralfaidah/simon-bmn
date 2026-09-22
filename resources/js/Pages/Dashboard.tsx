import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import StatusBadge from '@/Components/StatusBadge';
import { PageProps } from '@/types';

export default function Dashboard({ stats, recentLoans }: any) {
    const { auth } = usePage<PageProps>().props;
    return (
        <AuthenticatedLayout 
            header={
                <div>
                    <div className="flex items-center gap-2 text-xs font-semibold text-primary uppercase tracking-wider mb-1">
                        Portal Layanan BMN
                    </div>
                    <h1 className="text-2xl font-bold text-slate-900 tracking-tight">
                        Selamat datang, {auth.user.name}
                    </h1>
                    <p className="mt-1 text-xs sm:text-sm text-slate-500">
                        Akses operasional dan inventarisasi sesuai lingkup penugasan aktif.
                    </p>
                </div>
            }
        >
            <Head title="Beranda" />
            
            {/* Quick Actions Panel */}
            <section className="mb-6 rounded-xl border border-slate-200 bg-white p-4 sm:p-6 shadow-xs">
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h2 className="text-base font-semibold text-slate-900">Aksi Operasional Cepat</h2>
                        <p className="mt-1 text-xs text-slate-500">
                            Cari barang dinas, ajukan peminjaman baru, atau tindak lanjuti berkas yang menunggu.
                        </p>
                    </div>
                    <div className="grid grid-cols-2 gap-2 sm:flex sm:flex-wrap sm:items-center sm:gap-2.5 w-full sm:w-auto">
                        <Link className="simon-button w-full sm:w-auto text-center px-2 sm:px-4 text-xs sm:text-sm" href={route('loans.create')}>
                            Ajukan Peminjaman
                        </Link>
                        <Link className="simon-button-secondary w-full sm:w-auto text-center px-2 sm:px-4 text-xs sm:text-sm" href={route('assets.index')}>
                            Katalog Aset
                        </Link>
                        {auth.can.coordinate && (
                            <Link className="simon-button-secondary w-full sm:w-auto text-center px-2 sm:px-4 text-xs sm:text-sm" href={route('loans.approvals')}>
                                Antrean Persetujuan ({stats.approvals})
                            </Link>
                        )}
                        {(auth.can.coordinate || auth.can.administer) && (
                            <Link className="simon-button-secondary w-full sm:w-auto text-center px-2 sm:px-4 text-xs sm:text-sm" href={route('administration.index', { status: 'pending' })}>
                                Persetujuan Akun ({stats.account_approvals ?? 0})
                            </Link>
                        )}
                    </div>
                </div>
            </section>

            {/* Metric Stats Grid */}
            <div className="grid grid-cols-2 gap-2.5 sm:gap-4 sm:grid-cols-2 xl:grid-cols-4">
                {[
                    ['Aset dalam cakupan', stats.total, 'Total terdaftar'],
                    ['Kondisi baik', stats.kondisi_baik, 'Siap operasional'],
                    ['Perlu perhatian', stats.kondisi_rusak, 'Dalam perawatan/rusak'],
                    ['Pinjaman berjalan', stats.my_loans, 'Sedang Anda gunakan'],
                ].map(([label, value, hint]) => (
                    <section className="rounded-xl border border-slate-200 bg-white p-3.5 sm:p-5 shadow-xs hover:border-slate-300 transition-colors" key={label}>
                        <p className="text-[11px] sm:text-xs font-medium text-slate-500 truncate">{label}</p>
                        <p className="mt-1.5 sm:mt-2 text-xl sm:text-3xl font-bold tracking-tight text-slate-900">{value}</p>
                        <p className="mt-0.5 sm:mt-1 text-[10px] sm:text-[11px] text-slate-400 truncate">{hint}</p>
                    </section>
                ))}
            </div>

            {/* Recent Submissions Section */}
            <div className="mt-8 mb-4 flex items-center justify-between">
                <div>
                    <h2 className="text-base font-semibold text-slate-900">Pengajuan Terbaru Saya</h2>
                    <p className="text-xs text-slate-500">Status riwayat transaksi peminjaman barang dinas Anda</p>
                </div>
                <Link href={route('loans.index')} className="text-xs font-semibold text-primary hover:underline">
                    Lihat semua pengajuan →
                </Link>
            </div>

            <div className="space-y-2.5">
                {recentLoans.map((loan: any) => (
                    <Link 
                        key={loan.id} 
                        href={route('loans.show', loan.id)} 
                        className="rounded-lg border border-slate-200 bg-white p-4 shadow-xs flex flex-wrap items-center justify-between gap-3 hover:border-slate-300 hover:bg-slate-50/70 active:scale-[0.99] transition-all duration-150 ease-out"
                    >
                        <div className="flex items-center gap-3">
                            <span className="font-mono text-xs font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded">
                                #{loan.id}
                            </span>
                            <span className="text-sm font-medium text-slate-800">
                                {loan.purpose}
                            </span>
                        </div>
                        <StatusBadge status={loan.status} />
                    </Link>
                ))}
                {!recentLoans.length && (
                    <div className="rounded-lg border border-slate-200 border-dashed bg-white p-8 text-center text-xs text-slate-500">
                        Belum ada pengajuan aktif. Anda dapat memulai dengan memilih aset di katalog.
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
