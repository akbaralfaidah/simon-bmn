import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import StatusBadge, { LOAN_STAGE_CONFIGS } from '@/Components/StatusBadge';
import { PageProps } from '@/types';
import {
    Clock,
    Package,
    Calendar,
    AlertTriangle,
    CheckCircle2,
    ArrowRight
} from 'lucide-react';

export default function Dashboard({ stats, recentLoans, inProgressLoans = [] }: any) {
    const { auth } = usePage<PageProps>().props;
    const isStaff = auth.can.coordinate || auth.can.inspect;

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
                    isStaff
                        ? ['Peminjaman belum selesai', stats.in_progress_count ?? 0, 'Dalam proses di ruangan']
                        : ['Pinjaman berjalan', stats.my_loans, 'Sedang Anda gunakan'],
                ].map(([label, value, hint]) => (
                    <section className="rounded-xl border border-slate-200 bg-white p-3.5 sm:p-5 shadow-xs hover:border-slate-300 transition-colors" key={label}>
                        <p className="text-[11px] sm:text-xs font-medium text-slate-500 truncate">{label}</p>
                        <p className="mt-1.5 sm:mt-2 text-xl sm:text-3xl font-bold tracking-tight text-slate-900">{value}</p>
                        <p className="mt-0.5 sm:mt-1 text-[10px] sm:text-[11px] text-slate-400 truncate">{hint}</p>
                    </section>
                ))}
            </div>

            {/* In-Progress Loans Notification Panel for Coordinator & PJ */}
            {isStaff && (
                <section className="mt-8 mb-4">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-3">
                        <div className="flex items-center gap-2.5">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-50 border border-amber-200 text-amber-700 shadow-2xs shrink-0">
                                <Clock className="h-4 w-4" />
                            </div>
                            <div>
                                <h2 className="text-base font-semibold text-slate-900">
                                    Peminjaman Masih Berjalan (Belum Selesai)
                                </h2>
                                <p className="text-xs text-slate-500">
                                    Pantau transaksi aktif, peminjam yang belum mengembalikan, dan proses serah-terima
                                </p>
                            </div>
                        </div>
                        <Link href={route('loans.index')} className="text-xs font-semibold text-primary hover:underline inline-flex items-center gap-1 self-start sm:self-auto">
                            <span>Lihat Semua Peminjaman</span>
                            <ArrowRight className="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    {inProgressLoans && inProgressLoans.length > 0 ? (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                            {inProgressLoans.map((loan: any) => {
                                const stageConfig = LOAN_STAGE_CONFIGS[loan.status];
                                const currentStep = stageConfig?.stage ?? 0;

                                return (
                                    <Link
                                        key={loan.id}
                                        href={route('loans.show', loan.id)}
                                        className={`rounded-xl border p-4 shadow-xs transition-all duration-150 hover:shadow-sm hover:border-primary/50 block bg-white ${loan.is_overdue
                                                ? 'border-red-300 bg-red-50/30'
                                                : 'border-slate-200 hover:bg-slate-50/50'
                                            }`}
                                    >
                                        <div className="flex items-start justify-between gap-2 mb-2">
                                            <div className="flex items-center gap-2 min-w-0">
                                                <span className="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded shrink-0">
                                                    #{loan.id}
                                                </span>
                                                <span className="text-xs font-bold text-slate-900 truncate">
                                                    {loan.user?.name || 'Peminjam'}
                                                </span>
                                            </div>
                                            <div className="flex items-center gap-1.5 shrink-0">
                                                {loan.is_overdue && (
                                                    <span className="inline-flex items-center gap-1 rounded bg-red-100 px-2 py-0.5 text-[10px] font-bold text-red-700 uppercase tracking-wider animate-pulse">
                                                        <AlertTriangle className="h-3 w-3" />
                                                        Lewat Batas
                                                    </span>
                                                )}
                                                <StatusBadge status={loan.status} type="loan" />
                                            </div>
                                        </div>

                                        <p className="text-xs text-slate-600 font-medium line-clamp-1 mb-2.5">
                                            {loan.purpose}
                                        </p>

                                        {/* Mini SOP Step Progress */}
                                        {currentStep > 0 && currentStep <= 5 && (
                                            <div className="mb-3">
                                                <div className="flex items-center justify-between text-[10px] font-medium text-slate-400 mb-1">
                                                    <span>Alur SOP</span>
                                                    <span className="text-slate-600 font-semibold">Tahap {currentStep} dari 5</span>
                                                </div>
                                                <div className="grid grid-cols-5 gap-1">
                                                    {[1, 2, 3, 4, 5].map((step) => (
                                                        <div
                                                            key={step}
                                                            className={`h-1.5 rounded-full transition-colors ${
                                                                step < currentStep
                                                                    ? 'bg-emerald-500'
                                                                    : step === currentStep
                                                                    ? 'bg-indigo-600'
                                                                    : 'bg-slate-100'
                                                            }`}
                                                            title={`Tahap ${step}`}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                        )}

                                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5 text-[11px] text-slate-500 pt-2.5 border-t border-slate-100">
                                            <span className="inline-flex items-center gap-1">
                                                <Package className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                                <strong className="font-medium text-slate-700 truncate max-w-[180px]">
                                                    {loan.asset_names?.join(', ') || `${loan.items_count} barang`}
                                                </strong>
                                            </span>
                                            <span>•</span>
                                            <span className="inline-flex items-center gap-1">
                                                <Calendar className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                                <span>Batas: <strong className={loan.is_overdue ? 'text-red-600 font-bold' : 'text-slate-700 font-medium'}>{loan.end_date}</strong></span>
                                            </span>
                                            {loan.user?.unit && (
                                                <>
                                                    <span>•</span>
                                                    <span className="truncate max-w-[150px] text-slate-400">{loan.user.unit}</span>
                                                </>
                                            )}
                                        </div>
                                    </Link>
                                );
                            })}
                        </div>
                    ) : (
                        <div className="rounded-xl border border-slate-200 border-dashed bg-white p-6 text-center text-xs text-slate-500">
                            <CheckCircle2 className="h-6 w-6 text-emerald-500 mx-auto mb-1.5" />
                            <p className="font-semibold text-slate-700">Tidak ada peminjaman yang tertunda atau berjalan</p>
                            <p className="mt-0.5 text-slate-400">Seluruh transaksi peminjaman pada ruangan Anda telah diselesaikan.</p>
                        </div>
                    )}
                </section>
            )}

            {/* Recent Submissions Section (My Loans) */}
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
                        <StatusBadge status={loan.status} type="loan" />
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
