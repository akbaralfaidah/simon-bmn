import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import MediaGallery from '@/Components/MediaGallery';
import ActionForm, { Field } from '@/Components/ActionForm';
import StatusBadge from '@/Components/StatusBadge';
import SignBastModal from '@/Components/SignBastModal';
import { PageProps } from '@/types';
import {
    ArrowLeft,
    Check,
    CheckCircle2,
    Clock,
    FileEdit,
    FileSignature,
    PackageCheck,
    ClipboardCheck,
    AlertTriangle,
    XCircle,
    Ban,
    PenLine,
    Download,
    UserCheck,
} from 'lucide-react';

const notes: Field = { name: 'notes', label: 'Catatan pemeriksaan / kelengkapan', type: 'textarea' };
const reason: Field = { name: 'reason', label: 'Alasan', type: 'textarea' };

export default function Show({ loan, documents, canDecide, canInspect, returnFollowups }: any) {
    const authUser = usePage<PageProps>().props.auth.user as any;
    const owner = authUser.id === loan.user_id;
    const hasSignature = !!authUser?.profile?.has_signature || !!authUser?.profile?.signature_path;

    const [signModalState, setSignModalState] = useState<{
        bast: any;
        role: 'peminjam' | 'pj' | 'koordinator';
        roleLabel: string;
    } | null>(null);

    const pendingPhotos = loan.items.some((item: any) => item.media?.some((photo: any) => ['queued', 'processing'].includes(photo.processing_status)));
    const { start, stop } = usePoll(10000, { only: ['loan'] }, { autoStart: false });
    useEffect(() => { if (pendingPhotos) start(); else stop(); return stop; }, [pendingPhotos, start, stop]);
    const action = (name: string, label: string, fields: Field[] = [], initial: Record<string, any> = {}) => <ActionForm key={name} title={label} url={route('loans.action', { loan: loan.id, action: name })} fields={fields} initial={initial} />;
    const hasRepair = loan.items.some((item: any) => item.status === 'needs_repair');
    const hasLoanBastVerified = documents.some((d: any) => d.bast_type === 'loan' && d.status === 'verified');

    const SOP_STAGES = [
        {
            step: 1,
            title: 'Tahap 1',
            name: 'Pengajuan',
            icon: FileEdit,
            desc: 'Pengisian formulir & pengajuan',
        },
        {
            step: 2,
            title: 'Tahap 2',
            name: 'Persetujuan',
            icon: UserCheck,
            desc: 'Persetujuan Koordinator BMN',
        },
        {
            step: 3,
            title: 'Tahap 3',
            name: 'BAST & Serah Fisik',
            icon: FileSignature,
            desc: 'TTD dokumen & serah terima fisik barang',
        },
        {
            step: 4,
            title: 'Tahap 4',
            name: 'Penggunaan',
            icon: PackageCheck,
            desc: 'Masa aktif pinjam pakai BMN',
        },
        {
            step: 5,
            title: 'Tahap 5',
            name: 'Pengembalian',
            icon: ClipboardCheck,
            desc: 'Pemeriksaan fisik & penutupan',
        },
    ];

    const getStepState = (stepNumber: number) => {
        if (loan.status === 'rejected') {
            if (stepNumber === 1) return 'completed';
            if (stepNumber === 2) return 'rejected';
            return 'upcoming';
        }
        if (loan.status === 'cancelled') {
            return 'cancelled';
        }

        let currentStep = 1;
        if (loan.status === 'draft' || loan.status === 'revision_requested') currentStep = 1;
        else if (loan.status === 'pending_approval') currentStep = 2;
        else if (loan.status === 'approved') currentStep = 3;
        else if (loan.status === 'active') currentStep = 4;
        else if (loan.status === 'returning') currentStep = 5;
        else if (loan.status === 'completed') currentStep = 6;

        if (loan.status === 'completed' || stepNumber < currentStep) {
            return 'completed';
        }
        if (stepNumber === currentStep) {
            return 'active';
        }
        return 'upcoming';
    };

    const getSopGuidance = () => {
        if (hasRepair) {
            return {
                icon: AlertTriangle,
                title: 'Perhatian: Perbaikan BMN Diperlukan (SOP Pengembalian Butir 3)',
                desc: 'Pemeriksaan pengembalian menunjukkan barang tidak sesuai kondisi awal. Proses penutupan terkunci otomatis. Peminjam wajib memperbaiki BMN hingga kembali ke kondisi awal sebelum mengajukan pemeriksaan ulang.',
                type: 'danger',
            };
        }
        switch (loan.status) {
            case 'draft':
            case 'revision_requested':
                return {
                    icon: FileEdit,
                    title: 'Tahap 1 SOP: Pengajuan Peminjaman BMN',
                    desc: 'Peminjam mengisi rincian barang dan keperluan. Klik "Kirim pengajuan" untuk menyampaikan formulir ke Koordinator BMN.',
                    type: 'info',
                };
            case 'pending_approval':
                return {
                    icon: UserCheck,
                    title: 'Tahap 2 SOP: Persetujuan Koordinator BMN',
                    desc: 'Menunggu persetujuan Koordinator BMN. Setelah disetujui, Formulir Pinjam Pakai BMN otomatis di-generate untuk dicetak.',
                    type: 'info',
                };
            case 'approved':
                if (!hasLoanBastVerified) {
                    return {
                        icon: FileSignature,
                        title: 'Tahap 3 SOP: Penandatanganan Formulir Pinjam Pakai',
                        desc: 'Tanda Tangan Menggunakan Tanda Tangan Digital atau Unduh formulir Word (.docx) di tab Dokumen, cetak, tandatangani bersama PJ Ruangan, lalu unggah berkas PDF bertanda tangan untuk diverifikasi Koordinator.',
                        type: 'warning',
                    };
                }
                return {
                    icon: PackageCheck,
                    title: 'Tahap 3 SOP: Penyerahan Fisik Barang BMN',
                    desc: 'Dokumen pinjam pakai telah diverifikasi Koordinator. PJ Ruangan menyerahkan fisik barang di ruangan dan peminjam mengonfirmasi penerimaan.',
                    type: 'info',
                };
            case 'active':
                return {
                    icon: PackageCheck,
                    title: 'Tahap 4 SOP: Penggunaan & Pengembalian BMN',
                    desc: 'Barang sedang dalam masa pinjam pakai. Jika masa peminjaman selesai, bawa fisik barang ke PJ Ruangan dan klik "Ajukan pengembalian".',
                    type: 'success',
                };
            case 'returning':
                return {
                    icon: ClipboardCheck,
                    title: 'Tahap 5 SOP: Pemeriksaan Fisik & Formulir Pengembalian',
                    desc: 'PJ Ruangan memeriksa fisik dan kelengkapan barang di hadapan peminjam. Jika sesuai kondisi awal, formulir pengembalian diterbitkan untuk ditandatangani dan ditutup oleh Koordinator.',
                    type: 'info',
                };
            case 'completed':
                return {
                    icon: CheckCircle2,
                    title: 'Selesai: Peminjaman & Pengembalian Tuntas',
                    desc: 'Seluruh tahapan peminjaman dan pengembalian BMN telah selesai sesuai SOP BMN.',
                    type: 'success',
                };
            case 'rejected':
                return {
                    icon: XCircle,
                    title: 'Pengajuan Ditolak',
                    desc: 'Pengajuan peminjaman tidak disetujui oleh Koordinator BMN.',
                    type: 'danger',
                };
            case 'cancelled':
                return {
                    icon: Ban,
                    title: 'Pengajuan Dibatalkan',
                    desc: 'Peminjaman telah dibatalkan.',
                    type: 'neutral',
                };
            default:
                return null;
        }
    };

    const guidance = getSopGuidance();

    return <AuthenticatedLayout header={<div><Link href={route('loans.index')} className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#015850] hover:underline"><ArrowLeft className="h-4 w-4" /><span>Daftar peminjaman</span></Link><h1 className="mt-3 text-2xl font-bold">Peminjaman #{loan.id}</h1></div>}>
        <Head title={'Peminjaman #' + loan.id} />

        {/* Alur Tahapan Prosedur SOP BMN (Tahap 1 - 5) */}
        <div className="mb-6 rounded-xl border border-slate-200 bg-white p-4 sm:p-5 shadow-xs">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3 mb-4">
                <div>
                    <h3 className="text-xs font-bold uppercase tracking-wider text-slate-500">
                        Alur Tahapan Prosedur SOP BMN
                    </h3>
                    <p className="text-xs text-slate-400 mt-0.5">
                        Proses peminjaman dan pengembalian BMN terbagi dalam 5 tahapan terstandarisasi.
                    </p>
                </div>
                {loan.status === 'completed' && (
                    <span className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-semibold text-emerald-700">
                        <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600" />
                        Tahap 1 s.d. 5 Selesai Tuntas
                    </span>
                )}
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-5 gap-2.5">
                {SOP_STAGES.map((s) => {
                    const state = getStepState(s.step);
                    const StepIcon = s.icon;

                    return (
                        <div
                            key={s.step}
                            className={`relative flex items-center gap-3 rounded-xl border p-3 transition-all sm:flex-col sm:items-center sm:text-center sm:py-3.5 sm:px-2 ${
                                state === 'completed'
                                    ? 'border-emerald-200 bg-emerald-50/50 text-emerald-950 shadow-2xs'
                                    : state === 'active'
                                        ? 'border-[#015850] bg-teal-50/60 text-[#015850] ring-2 ring-[#015850]/20 shadow-xs'
                                        : state === 'rejected'
                                            ? 'border-red-200 bg-red-50 text-red-900'
                                            : 'border-slate-200 bg-slate-50/40 text-slate-400'
                            }`}
                        >
                            <div
                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg font-semibold transition-all ${
                                    state === 'completed'
                                        ? 'bg-emerald-600 text-white shadow-xs'
                                        : state === 'active'
                                            ? 'bg-[#015850] text-white shadow-xs'
                                            : state === 'rejected'
                                                ? 'bg-red-600 text-white'
                                                : 'bg-white border border-slate-200 text-slate-400'
                                }`}
                            >
                                {state === 'completed' ? (
                                    <Check className="h-4 w-4 stroke-[2.5]" />
                                ) : state === 'rejected' ? (
                                    <XCircle className="h-4 w-4" />
                                ) : (
                                    <StepIcon className="h-4 w-4" />
                                )}
                            </div>

                            <div className="min-w-0 flex-1 sm:flex-initial">
                                <div className="flex items-center gap-1.5 sm:justify-center">
                                    <span className="text-[11px] font-bold uppercase tracking-wider opacity-75">
                                        {s.title}
                                    </span>
                                    {state === 'active' && (
                                        <span className="inline-block h-1.5 w-1.5 rounded-full bg-[#015850] animate-pulse" />
                                    )}
                                </div>
                                <div className="text-xs font-bold leading-tight truncate mt-0.5">
                                    {s.name}
                                </div>
                                <div className="hidden sm:block text-[10px] text-slate-500 mt-1 line-clamp-1 opacity-80">
                                    {s.desc}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>
        </div>

        {guidance && (() => {
            const GuidanceIcon = guidance.icon;
            return (
                <div
                    className={`mb-6 rounded-xl border p-4 shadow-xs transition-all ${
                        guidance.type === 'danger'
                            ? 'border-red-200 bg-red-50 text-red-900'
                            : guidance.type === 'warning'
                                ? 'border-amber-200 bg-amber-50 text-amber-900'
                                : guidance.type === 'success'
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                                    : 'border-teal-200 bg-teal-50 text-[#015850]'
                    }`}
                >
                    <div className="flex items-start gap-3.5">
                        <div
                            className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-lg ${
                                guidance.type === 'danger'
                                    ? 'bg-red-100 text-red-600'
                                    : guidance.type === 'warning'
                                        ? 'bg-amber-100 text-amber-700'
                                        : guidance.type === 'success'
                                            ? 'bg-emerald-100 text-emerald-600'
                                            : 'bg-teal-100 text-[#015850]'
                            }`}
                        >
                            <GuidanceIcon className="h-5 w-5" />
                        </div>
                        <div className="pt-0.5">
                            <h3 className="font-semibold text-base leading-snug">{guidance.title}</h3>
                            <p className="mt-1 text-sm opacity-90 leading-relaxed">{guidance.desc}</p>
                        </div>
                    </div>
                </div>
            );
        })()}

        <div className="simon-card mb-6"><div className="flex flex-wrap items-start justify-between gap-3"><h2 className="text-xl font-semibold">{loan.purpose}</h2><StatusBadge status={loan.status} type="loan" /></div><p className="mt-3 text-sm text-slate-600">{loan.user.name} · {loan.start_date} sampai {loan.end_date}</p>{loan.decision_reason && <p className="mt-3">Alasan: {loan.decision_reason}</p>}
            <div className="mt-5 flex flex-wrap gap-2">
                {owner && loan.status === 'draft' && action('submit', 'Kirim pengajuan')}
                {owner && ['draft', 'revision_requested'].includes(loan.status) && <Link className="simon-button-secondary" href={route('loans.edit', loan.id)}>Edit draf / revisi</Link>}
                {canDecide && loan.status === 'pending_approval' && action('revise', 'Minta revisi', [reason])}
                {canDecide && loan.status === 'pending_approval' && <><ActionForm title="Setujui peminjaman" url={route('loans.approve', loan.id)} />{action('reject', 'Tolak dengan alasan', [reason])}</>}
                {owner && ['draft', 'revision_requested', 'pending_approval', 'approved'].includes(loan.status) && action('cancel', 'Batalkan pengajuan', [reason])}
                {owner && loan.status === 'active' && !loan.requested_end_date && action('extend', 'Ajukan perpanjangan', [{ name: 'end_date', label: 'Tanggal selesai baru', type: 'date' }, reason])}
                {canDecide && loan.requested_end_date && <>{action('approve-extension', 'Setujui perpanjangan')}{action('reject-extension', 'Tolak perpanjangan', [reason])}</>}
            </div>{loan.requested_end_date && <p className="mt-4 text-sm">Perpanjangan diminta sampai {loan.requested_end_date}. {loan.extension_reason}</p>}
        </div>
        <h2 className="mb-3 text-lg font-bold">Barang & serah-terima</h2>
        <p className="mb-4 text-sm text-slate-600">Koordinator menyetujui → PJ memeriksa → dokumen diverifikasi → PJ menyerahkan → peminjam menerima. Pengembalian diperiksa PJ dan ditutup koordinator.</p>
        <div className="space-y-3">{loan.items.map((item: any) => <section className="simon-card" key={item.id}>
            <div className="flex flex-wrap justify-between gap-3"><h3 className="font-semibold">{item.asset.name} · NUP {item.asset.nup || '—'}</h3><StatusBadge status={item.status} /></div>
            {item.notes && <p className="mt-2 text-sm text-slate-600">{item.notes}</p>}
            {item.checklist?.notes && <p className="mt-2 text-sm">Kelengkapan sebelum diserahkan: {item.checklist.notes}</p>}
            {item.checklist?.return_completeness && <p className="mt-2 text-sm">Kelengkapan pengembalian: {item.checklist.return_completeness === 'complete' ? 'Lengkap' : 'Tidak lengkap — perlu tindak lanjut'}</p>}
            {!!item.media?.length && <div className="mt-3"><MediaGallery photos={item.media} poll={false} /></div>}
            {item.condition_before && <p className="mt-2 text-sm">Kondisi saat diserahkan: {item.condition_before} · Saat dikembalikan: {item.condition_after || 'Belum diperiksa'}</p>}
            {item.status === 'needs_repair' && <div className="mt-3 rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900">
                <p className="font-semibold flex items-center gap-2">
                    <AlertTriangle className="h-4 w-4 text-red-600 shrink-0" />
                    <span>Sesuai SOP Pengembalian Butir 3: Barang perlu diperbaiki oleh peminjam</span>
                </p>
                <p className="mt-1 pl-6">Pemeriksaan fisik menunjukkan barang rusak atau tidak sesuai kondisi awal. Peminjam wajib memperbaiki BMN sesuai kondisi awal. Penutupan peminjaman terkunci otomatis hingga perbaikan selesai dan diperiksa ulang oleh PJ Ruangan.</p>
            </div>}
            {returnFollowups.filter((record: any) => record.item_id === item.id).map((record: any) => <div key={record.id} className="mt-3 rounded-lg border border-orange-200 bg-orange-50 p-3 text-sm"><p>Tindak lanjut pengembalian #{record.id} · <StatusBadge status={record.status} /></p><p className="mt-2">Penutupan memerlukan penyelesaian tindak lanjut dan BAST terverifikasi. Catatan ini bukan penetapan kesalahan peminjam.</p><Link className="mt-2 inline-block font-semibold text-[#015850]" href={route('workspace.index', 'incidents')}>Buka tindak lanjut</Link></div>)}
            <div className="mt-4 flex flex-wrap gap-2">
                {canInspect && ['approved', 'prepared', 'return_requested', 'inspected'].includes(item.status) && <ActionForm title="Tambah foto bukti" url={route('media.evidence')} fields={[{ name: 'images', label: 'Foto pemeriksaan (maks. 4 foto, 10 MB per foto)', type: 'images', hint: 'JPG, PNG atau WebP. Turunan WebP dibuat otomatis; sumber asli tetap privat.' }]} initial={{ parent_type: 'loan-item', parent_id: item.id }} />}
                {canInspect && item.status === 'approved' && action('prepare', 'Periksa kelengkapan', [notes], { item_ids: [item.id] })}
                {canInspect && item.status === 'prepared' && action('handover', 'Serahkan barang', [], { item_ids: [item.id] })}
                {owner && item.status === 'handed_over' && action('accept', 'Konfirmasi diterima', [], { item_ids: [item.id] })}
                {owner && item.status === 'active' && action('request-return', 'Ajukan pengembalian', [notes], { item_ids: [item.id] })}
                {owner && item.status === 'needs_repair' && action('request-return', 'Ajukan pemeriksaan ulang (Perbaikan selesai)', [notes], { item_ids: [item.id] })}
                {canInspect && item.status === 'return_requested' && action('inspect', 'Periksa pengembalian', [{ name: 'condition', label: 'Kondisi fisik', type: 'select', options: ['Baik', 'Rusak Ringan', 'Rusak Berat'].map(value => ({ value, label: value })) }, { name: 'completeness', label: 'Kelengkapan dibanding saat diserahkan', type: 'select', options: [{ value: 'complete', label: 'Lengkap' }, { value: 'incomplete', label: 'Tidak lengkap' }] }, notes], { item_ids: [item.id] })}
                {canDecide && item.status === 'inspected' && action('close', 'Tutup pengembalian', [], { item_ids: [item.id] })}
                {(canDecide || canInspect) && ['approved', 'prepared'].includes(item.status) && action('cancel-item', 'Batalkan item belum diserahkan', [reason], { item_ids: [item.id] })}
            </div>
        </section>)}</div>
        <h2 className="mb-3 mt-8 text-lg font-bold">Dokumen BAST</h2>
        <div className="space-y-4">
            {documents.map((doc: any) => {
                const sigs = doc.snapshot?.signatures || {};
                const canSignPeminjam = owner && !sigs.peminjam && doc.status !== 'verified';
                const canSignPj = canInspect && !sigs.pj && doc.status !== 'verified';
                const canSignKoor = canDecide && !sigs.koordinator && doc.status !== 'verified';

                return (
                    <section key={doc.id} className="simon-card">
                        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3">
                            <div>
                                <h3 className="font-semibold text-base text-slate-900">{doc.bast_number}</h3>
                                <p className="text-xs text-slate-500 capitalize">Jenis: BAST {doc.bast_type === 'return' ? 'Pengembalian' : 'Peminjaman'}</p>
                            </div>
                            <StatusBadge status={doc.status} />
                        </div>

                        {/* Signature Status Tracker */}
                        <div className="my-3.5 rounded-lg border border-slate-200 bg-slate-50/70 p-3 text-xs">
                            <p className="font-semibold text-slate-700 mb-2">Status Penandatanganan Digital:</p>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-2">
                                <div className={`rounded-md p-2.5 border transition-all ${sigs.peminjam ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600'}`}>
                                    <div className="flex items-center justify-between font-medium">
                                        <span>1. Peminjam</span>
                                        {sigs.peminjam ? (
                                            <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                        ) : (
                                            <Clock className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        )}
                                    </div>
                                    <div className="mt-1 text-[11px] truncate font-semibold">
                                        {sigs.peminjam ? sigs.peminjam.name : 'Belum ditandatangani'}
                                    </div>
                                </div>

                                <div className={`rounded-md p-2.5 border transition-all ${sigs.pj ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600'}`}>
                                    <div className="flex items-center justify-between font-medium">
                                        <span>2. PJ Ruangan</span>
                                        {sigs.pj ? (
                                            <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                        ) : (
                                            <Clock className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        )}
                                    </div>
                                    <div className="mt-1 text-[11px] truncate font-semibold">
                                        {sigs.pj ? sigs.pj.name : 'Belum ditandatangani'}
                                    </div>
                                </div>

                                <div className={`rounded-md p-2.5 border transition-all ${sigs.koordinator ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600'}`}>
                                    <div className="flex items-center justify-between font-medium">
                                        <span>3. Koordinator BMN</span>
                                        {sigs.koordinator ? (
                                            <CheckCircle2 className="h-3.5 w-3.5 text-emerald-600 shrink-0" />
                                        ) : (
                                            <Clock className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                        )}
                                    </div>
                                    <div className="mt-1 text-[11px] truncate font-semibold">
                                        {sigs.koordinator ? sigs.koordinator.name : 'Belum ditandatangani'}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <p className="my-3 text-xs text-slate-600">
                            Anda dapat membubuhkan tanda tangan secara digital dengan memasukkan kata sandi akun Anda, atau mengunduh dokumen formulir untuk ditandatangani secara basah.
                        </p>

                        <div className="flex flex-wrap items-center gap-2 pt-1">
                            {canSignPeminjam && (
                                <button
                                    type="button"
                                    onClick={() => setSignModalState({ bast: doc, role: 'peminjam', roleLabel: 'Peminjam' })}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-[#015850] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#014740] active:scale-[0.98] transition-all"
                                >
                                    <PenLine className="h-3.5 w-3.5 shrink-0" />
                                    <span>Tandatangani BAST (Peminjam)</span>
                                </button>
                            )}

                            {canSignPj && (
                                <button
                                    type="button"
                                    onClick={() => setSignModalState({ bast: doc, role: 'pj', roleLabel: 'Penanggung Jawab Ruangan' })}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-[#015850] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#014740] active:scale-[0.98] transition-all"
                                >
                                    <PenLine className="h-3.5 w-3.5 shrink-0" />
                                    <span>Tandatangani BAST (PJ Ruangan)</span>
                                </button>
                            )}

                            {canSignKoor && (
                                <button
                                    type="button"
                                    onClick={() => setSignModalState({ bast: doc, role: 'koordinator', roleLabel: 'Koordinator BMN' })}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-[#015850] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#014740] active:scale-[0.98] transition-all"
                                >
                                    <PenLine className="h-3.5 w-3.5 shrink-0" />
                                    <span>Tandatangani BAST (Koordinator BMN)</span>
                                </button>
                            )}

                            <a className="simon-button-secondary text-xs inline-flex items-center gap-1.5" href={route('basts.print', doc.id)}>
                                <Download className="h-3.5 w-3.5 shrink-0" />
                                <span>Unduh Formulir (.docx)</span>
                            </a>

                            {doc.status !== 'draft' && (
                                <a className="simon-button-secondary text-xs inline-flex items-center gap-1.5" href={route('basts.download', doc.id)}>
                                    <Download className="h-3.5 w-3.5 shrink-0" />
                                    <span>Unduh Berkas Selesai</span>
                                </a>
                            )}

                            {owner && doc.status === 'draft' && (
                                <ActionForm
                                    title="Unggah PDF fisik (alternatif)"
                                    url={route('basts.action', { bast: doc.id, action: 'upload' })}
                                    fields={[{ name: 'document', label: 'PDF bertanda tangan (maks. 10 MB)', type: 'file' }]}
                                />
                            )}

                            {canDecide && doc.status === 'uploaded' && (
                                <ActionForm
                                    title="Verifikasi tanda tangan & isi"
                                    url={route('basts.action', { bast: doc.id, action: 'verify' })}
                                    description="Unduh dan periksa isi, identitas, serta tanda tangan kedua pihak sebelum mengonfirmasi."
                                />
                            )}
                        </div>
                    </section>
                );
            })}
        </div>
        {!documents.length && <p className="simon-card text-sm text-slate-500">Dokumen tersedia setelah persetujuan.</p>}

        <SignBastModal
            isOpen={!!signModalState}
            bast={signModalState?.bast}
            role={signModalState?.role ?? null}
            roleLabel={signModalState?.roleLabel ?? ''}
            hasSignature={hasSignature}
            onClose={() => setSignModalState(null)}
        />
    </AuthenticatedLayout>;
}
