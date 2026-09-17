import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import MediaGallery from '@/Components/MediaGallery';
import ActionForm, { Field } from '@/Components/ActionForm';
import StatusBadge from '@/Components/StatusBadge';
import { PageProps } from '@/types';

const notes: Field = { name: 'notes', label: 'Catatan pemeriksaan / kelengkapan', type: 'textarea' };
const reason: Field = { name: 'reason', label: 'Alasan', type: 'textarea' };
export default function Show({ loan, documents, canDecide, canInspect, returnFollowups }: any) {
    const owner = usePage<PageProps>().props.auth.user.id === loan.user_id;
    const pendingPhotos = loan.items.some((item: any) => item.media?.some((photo: any) => ['queued', 'processing'].includes(photo.processing_status)));
    const { start, stop } = usePoll(10000, { only: ['loan'] }, { autoStart: false });
    useEffect(() => { if (pendingPhotos) start(); else stop(); return stop; }, [pendingPhotos, start, stop]);
    const action = (name: string, label: string, fields: Field[] = [], initial: Record<string, any> = {}) => <ActionForm key={name} title={label} url={route('loans.action', { loan: loan.id, action: name })} fields={fields} initial={initial} />;
    return <AuthenticatedLayout header={<div><Link href={route('loans.index')} className="text-sm text-[#015850]">← Daftar peminjaman</Link><h1 className="mt-3 text-2xl font-bold">Peminjaman #{loan.id}</h1></div>}>
        <Head title={'Peminjaman #' + loan.id} />
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
            {returnFollowups.filter((record: any) => record.item_id === item.id).map((record: any) => <div key={record.id} className="mt-3 rounded-lg border border-orange-200 bg-orange-50 p-3 text-sm"><p>Tindak lanjut pengembalian #{record.id} · <StatusBadge status={record.status} /></p><p className="mt-2">Penutupan memerlukan penyelesaian tindak lanjut dan BAST terverifikasi. Catatan ini bukan penetapan kesalahan peminjam.</p><Link className="mt-2 inline-block font-semibold text-[#015850]" href={route('workspace.index', 'incidents')}>Buka tindak lanjut</Link></div>)}
            <div className="mt-4 flex flex-wrap gap-2">
                {canInspect && ['approved', 'prepared', 'return_requested', 'inspected'].includes(item.status) && <ActionForm title="Tambah foto bukti" url={route('media.evidence')} fields={[{ name: 'images', label: 'Foto pemeriksaan (maks. 4 foto, 10 MB per foto)', type: 'images', hint: 'JPG, PNG atau WebP. Turunan WebP dibuat otomatis; sumber asli tetap privat.' }]} initial={{ parent_type: 'loan-item', parent_id: item.id }} />}
                {canInspect && item.status === 'approved' && action('prepare', 'Periksa kelengkapan', [notes], { item_ids: [item.id] })}
                {canInspect && item.status === 'prepared' && action('handover', 'Serahkan barang', [], { item_ids: [item.id] })}
                {owner && item.status === 'handed_over' && action('accept', 'Konfirmasi diterima', [], { item_ids: [item.id] })}
                {owner && item.status === 'active' && action('request-return', 'Ajukan pengembalian', [notes], { item_ids: [item.id] })}
                {canInspect && item.status === 'return_requested' && action('inspect', 'Periksa pengembalian', [{ name: 'condition', label: 'Kondisi fisik', type: 'select', options: ['Baik', 'Rusak Ringan', 'Rusak Berat'].map(value => ({ value, label: value })) }, { name: 'completeness', label: 'Kelengkapan dibanding saat diserahkan', type: 'select', options: [{ value: 'complete', label: 'Lengkap' }, { value: 'incomplete', label: 'Tidak lengkap' }] }, notes], { item_ids: [item.id] })}
                {canDecide && item.status === 'inspected' && action('close', 'Tutup pengembalian', [], { item_ids: [item.id] })}
                {canDecide && ['approved', 'prepared'].includes(item.status) && action('cancel-item', 'Batalkan item belum diserahkan', [reason], { item_ids: [item.id] })}
            </div>
        </section>)}</div>
        <h2 className="mb-3 mt-8 text-lg font-bold">Dokumen</h2>
        <div className="space-y-3">{documents.map((doc: any) => <section key={doc.id} className="simon-card"><div className="flex flex-wrap justify-between gap-3"><h3 className="font-semibold">{doc.bast_number}</h3><StatusBadge status={doc.status} /></div><p className="my-3 text-sm text-slate-600">Cetak sistem adalah draf. Unggah PDF bertanda tangan untuk diverifikasi.</p><div className="flex flex-wrap gap-2">
            <a className="simon-button-secondary" target="_blank" rel="noreferrer" href={route('basts.print', doc.id)}>Cetak draf</a>
            {owner && doc.status === 'draft' && <ActionForm title="Unggah dokumen bertanda tangan" url={route('basts.action', { bast: doc.id, action: 'upload' })} fields={[{ name: 'document', label: 'PDF bertanda tangan (maks. 10 MB)', type: 'file' }]} />}
            {doc.status !== 'draft' && <a className="simon-button-secondary" href={route('basts.download', doc.id)}>Unduh berkas</a>}
            {canDecide && doc.status === 'uploaded' && <ActionForm title="Verifikasi tanda tangan & isi" url={route('basts.action', { bast: doc.id, action: 'verify' })} description="Unduh dan periksa isi, identitas, serta tanda tangan kedua pihak sebelum mengonfirmasi." />}
        </div></section>)}</div>{!documents.length && <p className="simon-card text-sm text-slate-500">Dokumen tersedia setelah persetujuan.</p>}
    </AuthenticatedLayout>;
}
