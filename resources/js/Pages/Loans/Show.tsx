import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import MediaGallery from '@/Components/MediaGallery';
import ActionForm, { Field } from '@/Components/ActionForm';
import StatusBadge from '@/Components/StatusBadge';
import SignBastModal from '@/Components/SignBastModal';
import { PageProps } from '@/types';

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

    const getSopGuidance = () => {
        if (hasRepair) {
            return {
                title: 'Perhatian: Perbaikan BMN Diperlukan (SOP Pengembalian Butir 3)',
                desc: 'Pemeriksaan pengembalian menunjukkan barang tidak sesuai kondisi awal. Proses penutupan terkunci otomatis. Peminjam wajib memperbaiki BMN hingga kembali ke kondisi awal sebelum mengajukan pemeriksaan ulang.',
                type: 'danger',
            };
        }
        switch (loan.status) {
            case 'draft':
            case 'revision_requested':
                return {
                    title: 'Tahap 1 SOP: Pengajuan Peminjaman BMN',
                    desc: 'Peminjam mengisi rincian barang dan keperluan. Klik "Kirim pengajuan" untuk menyampaikan formulir ke Koordinator BMN.',
                    type: 'info',
                };
            case 'pending_approval':
                return {
                    title: 'Tahap 2 SOP: Persetujuan Koordinator BMN',
                    desc: 'Menunggu persetujuan Koordinator BMN. Setelah disetujui, Formulir Pinjam Pakai BMN otomatis di-generate untuk dicetak.',
                    type: 'info',
                };
            case 'approved':
                if (!hasLoanBastVerified) {
                    return {
                        title: 'Tahap 3 SOP: Penandatanganan Formulir Pinjam Pakai',
                        desc: 'Tanda Tangan Menggunakan Tanda Tangan Digital atau Unduh formulir Word (.docx) di tab Dokumen, cetak, tandatangani bersama PJ Ruangan, lalu unggah berkas PDF bertanda tangan untuk diverifikasi Koordinator.',
                        type: 'warning',
                    };
                }
                return {
                    title: 'Tahap 3 SOP: Penyerahan Fisik Barang BMN',
                    desc: 'Dokumen pinjam pakai telah diverifikasi Koordinator. PJ Ruangan menyerahkan fisik barang di ruangan dan peminjam mengonfirmasi penerimaan.',
                    type: 'info',
                };
            case 'active':
                return {
                    title: 'Tahap 4 SOP: Penggunaan & Pengembalian BMN',
                    desc: 'Barang sedang dalam masa pinjam pakai. Jika masa peminjaman selesai, bawa fisik barang ke PJ Ruangan dan klik "Ajukan pengembalian".',
                    type: 'success',
                };
            case 'returning':
                return {
                    title: 'Tahap 5 SOP: Pemeriksaan Fisik & Formulir Pengembalian',
                    desc: 'PJ Ruangan memeriksa fisik dan kelengkapan barang di hadapan peminjam. Jika sesuai kondisi awal, formulir pengembalian diterbitkan untuk ditandatangani dan ditutup oleh Koordinator.',
                    type: 'info',
                };
            case 'completed':
                return {
                    title: 'Selesai: Peminjaman & Pengembalian Tuntas',
                    desc: 'Seluruh tahapan peminjaman dan pengembalian BMN telah selesai sesuai SOP BMN.',
                    type: 'success',
                };
            case 'rejected':
                return {
                    title: 'Pengajuan Ditolak',
                    desc: 'Pengajuan peminjaman tidak disetujui oleh Koordinator BMN.',
                    type: 'danger',
                };
            case 'cancelled':
                return {
                    title: 'Pengajuan Dibatalkan',
                    desc: 'Peminjaman telah dibatalkan.',
                    type: 'neutral',
                };
            default:
                return null;
        }
    };

    const guidance = getSopGuidance();

    return <AuthenticatedLayout header={<div><Link href={route('loans.index')} className="text-sm text-[#015850]">← Daftar peminjaman</Link><h1 className="mt-3 text-2xl font-bold">Peminjaman #{loan.id}</h1></div>}>
        <Head title={'Peminjaman #' + loan.id} />
        {guidance && (
            <div className={`mb-6 rounded-xl border p-4 shadow-xs transition-all ${guidance.type === 'danger'
                    ? 'border-red-300 bg-red-50 text-red-900'
                    : guidance.type === 'warning'
                        ? 'border-amber-300 bg-amber-50 text-amber-900'
                        : guidance.type === 'success'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-900'
                            : 'border-teal-200 bg-teal-50 text-[#015850]'
                }`}>
                <div className="flex items-start gap-3">
                    <span className="text-xl">
                        {guidance.type === 'danger' ? '🚨' : guidance.type === 'warning' ? '📝' : guidance.type === 'success' ? '✅' : 'ℹ️'}
                    </span>
                    <div>
                        <h3 className="font-semibold text-base">{guidance.title}</h3>
                        <p className="mt-1 text-sm opacity-90">{guidance.desc}</p>
                    </div>
                </div>
            </div>
        )}
        <div className="simon-card mb-6"><div className="flex flex-wrap items-start justify-between gap-3"><h2 className="text-xl font-semibold">{loan.purpose}</h2><StatusBadge status={loan.status} /></div><p className="mt-3 text-sm text-slate-600">{loan.user.name} · {loan.start_date} sampai {loan.end_date}</p>{loan.decision_reason && <p className="mt-3">Alasan: {loan.decision_reason}</p>}
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
                <p className="font-semibold">⚠️ Sesuai SOP Pengembalian Butir 3: Barang perlu diperbaiki oleh peminjam</p>
                <p className="mt-1">Pemeriksaan fisik menunjukkan barang rusak atau tidak sesuai kondisi awal. Peminjam wajib memperbaiki BMN sesuai kondisi awal. Penutupan peminjaman terkunci otomatis hingga perbaikan selesai dan diperiksa ulang oleh PJ Ruangan.</p>
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
                                <div className={`rounded-md p-2 border ${sigs.peminjam ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600'}`}>
                                    <div className="font-medium">1. Peminjam</div>
                                    <div className="mt-0.5 text-[11px] truncate">
                                        {sigs.peminjam ? `✓ ${sigs.peminjam.name}` : '⏳ Belum ditandatangani'}
                                    </div>
                                </div>

                                <div className={`rounded-md p-2 border ${sigs.pj ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600'}`}>
                                    <div className="font-medium">2. PJ Ruangan</div>
                                    <div className="mt-0.5 text-[11px] truncate">
                                        {sigs.pj ? `✓ ${sigs.pj.name}` : '⏳ Belum ditandatangani'}
                                    </div>
                                </div>

                                <div className={`rounded-md p-2 border ${sigs.koordinator ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white text-slate-600'}`}>
                                    <div className="font-medium">3. Koordinator BMN</div>
                                    <div className="mt-0.5 text-[11px] truncate">
                                        {sigs.koordinator ? `✓ ${sigs.koordinator.name}` : '⏳ Belum ditandatangani'}
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
                                    <span>✍️</span>
                                    <span>Tandatangani BAST (Peminjam)</span>
                                </button>
                            )}

                            {canSignPj && (
                                <button
                                    type="button"
                                    onClick={() => setSignModalState({ bast: doc, role: 'pj', roleLabel: 'Penanggung Jawab Ruangan' })}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-[#015850] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#014740] active:scale-[0.98] transition-all"
                                >
                                    <span>✍️</span>
                                    <span>Tandatangani BAST (PJ Ruangan)</span>
                                </button>
                            )}

                            {canSignKoor && (
                                <button
                                    type="button"
                                    onClick={() => setSignModalState({ bast: doc, role: 'koordinator', roleLabel: 'Koordinator BMN' })}
                                    className="inline-flex items-center gap-1.5 rounded-lg bg-[#015850] px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-[#014740] active:scale-[0.98] transition-all"
                                >
                                    <span>✍️</span>
                                    <span>Tandatangani BAST (Koordinator BMN)</span>
                                </button>
                            )}

                            <a className="simon-button-secondary text-xs" href={route('basts.print', doc.id)}>
                                Unduh Formulir (.docx)
                            </a>

                            {doc.status !== 'draft' && (
                                <a className="simon-button-secondary text-xs" href={route('basts.download', doc.id)}>
                                    Unduh Berkas Selesai
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
