import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import MediaGallery from '@/Components/MediaGallery';
import ActionForm, { Field } from '@/Components/ActionForm';
import StatusBadge from '@/Components/StatusBadge';

const stages: Record<string, string> = { draft: 'Disposisi', active: 'Pemeriksaan PJ', sub_review: 'Review Subkoordinator', coordinator_review: 'Review Koordinator', head_review: 'Pengesahan Kepala TU', authorized: 'Siap diarsipkan', closed: 'Arsip final' };
const actions: Record<string, [string, string]> = { draft: ['start', 'Catat disposisi Kepala TU'], active: ['submit', 'Kirim kertas kerja PJ'], sub_review: ['sub-review', 'Catat review Subkoordinator'], coordinator_review: ['coordinate-review', 'Review Koordinator'], head_review: ['authorize', 'Catat pengesahan Kepala TU'] };
const notes: Field = { name: 'notes', label: 'Catatan / alasan', type: 'textarea' };
export default function Inventory({ session, canClose, canCheck }: any) {
    const pendingPhotos = session.items.some((item: any) => item.media?.some((photo: any) => ['queued', 'processing'].includes(photo.processing_status)));
    const { start, stop } = usePoll(10000, { only: ['session'] }, { autoStart: false });
    useEffect(() => { if (pendingPhotos) start(); else stop(); return stop; }, [pendingPhotos, start, stop]);
    const next = actions[session.status];
    const external = next && ['start', 'sub-review', 'authorize'].includes(next[0]);
    const evidence = external || next?.[0] === 'submit';
    const fields: Field[] = [notes, ...(external ? [{ name: 'officer_name', label: 'Nama pejabat pada bukti pengesahan' }] : []), ...(evidence ? [{ name: 'reference_number', label: 'Nomor dokumen / kertas kerja' }, { name: 'signed_date', label: 'Tanggal penandatanganan', type: 'date' as const }, { name: 'document', label: 'Bukti PDF bertanda tangan (maks. 10 MB)', type: 'file' as const }] : [])];
    return <AuthenticatedLayout header={<><Link className="text-sm text-[#015850]" href={route('workspace.index', 'inventory')}>← Inventarisasi</Link><h1 className="mt-3 text-2xl font-bold">{session.name}</h1></>}>
        <Head title={session.name} />
        <div className="simon-card mb-6">
            <p className="font-semibold">Tahap: {stages[session.status] || session.status}</p>
            <p className="mt-2 text-sm text-slate-600">Disposisi → pemeriksaan PJ → Subkoordinator → Koordinator → Kepala TU → arsip. Nama pejabat dan berkas adalah bukti eksternal yang dicatat petugas, bukan tanda tangan otomatis sistem.</p>
            <div className="mt-4 flex flex-wrap gap-2">
                {next && (next[0] === 'submit' ? canCheck : canClose) && <ActionForm title={next[1]} url={route('inventory.review', [session.id, next[0]])} fields={fields} initial={{ version: session.version }} />}
                {canClose && ['sub_review', 'coordinator_review', 'head_review', 'authorized'].includes(session.status) && <ActionForm title="Kembalikan untuk koreksi PJ" url={route('inventory.review', [session.id, 'revise'])} fields={[notes]} initial={{ version: session.version }} />}
                {canClose && session.status === 'authorized' && <ActionForm title="Arsipkan hasil final" url={route('inventory.close', session.id)} description="Snapshot final tidak mengubah master aset. Semua temuan dan bukti review tetap disimpan." />}
                {canClose && <><ActionForm title="Buat draf DBR" url={route('documents.store')} initial={{ parent_type: 'inventory', parent_id: session.id, template: 'dbr' }} /><ActionForm title="Buat lembar inventarisasi" url={route('documents.store')} initial={{ parent_type: 'inventory', parent_id: session.id, template: 'inventory' }} /></>}
            </div>
        </div>
        <div className="space-y-4">{session.items.map((item: any) => <section className="simon-card" key={item.id}>
            <div className="flex flex-wrap justify-between gap-3"><h2 className="font-semibold">{item.snapshot?.name || item.asset.name} · NUP {item.snapshot?.nup || '—'}</h2><StatusBadge status={item.status} /></div>
            <p className="my-3 text-sm text-slate-600">{item.notes || 'Belum ada catatan.'}</p>
            {!!item.media?.length && <div className="mb-3"><MediaGallery photos={item.media} poll={false} /></div>}
            {canCheck && session.status === 'active' && <div className="mb-3"><ActionForm title="Tambah foto pemeriksaan" url={route('media.evidence')} fields={[{ name: 'images', label: 'Foto bukti (maks. 4 foto, 10 MB per foto)', type: 'images', hint: 'Turunan WebP otomatis, sumber asli privat.' }]} initial={{ parent_type: 'inventory-item', parent_id: item.id }} /></div>}
            {item.finding && <p className="mb-3 rounded-xl bg-amber-50 p-3 text-sm">Temuan: {item.finding.description} ({item.finding.status}). Master aset tidak berubah otomatis.</p>}
            {canCheck && session.status === 'active' && <ActionForm title="Catat pemeriksaan fisik" url={route('inventory.check', item.id)} fields={[{ name: 'status', label: 'Hasil pemeriksaan', type: 'select', options: [{ value: 'found', label: 'Ditemukan sesuai' }, { value: 'missing', label: 'Tidak ditemukan' }, { value: 'damaged', label: 'Rusak' }, { value: 'wrong_location', label: 'Beda lokasi' }, { value: 'wrong_identity', label: 'Beda identitas' }] }, { ...notes, required: false }, { name: 'finding_description', label: 'Uraian temuan (wajib bila tidak sesuai)', type: 'textarea', required: false }]} />}
        </section>)}</div>
        <section className="simon-card mt-6"><h2 className="font-bold">Riwayat review & bukti</h2><ol className="mt-3 space-y-3">{session.reviews.map((review: any, index: number) => <li key={index} className="border-b border-slate-100 pb-3"><p className="text-sm font-semibold">{review.officer_name || review.actor_name} · {({ start: 'Disposisi', submit: 'Kertas kerja PJ', 'sub-review': 'Review Subkoordinator', 'coordinate-review': 'Review Koordinator', authorize: 'Pengesahan Kepala TU', revise: 'Permintaan koreksi' } as Record<string, string>)[review.action]}</p><p className="mt-1 text-sm text-slate-600">{review.notes}</p>{review.reference_number && <p className="text-xs text-slate-500">{review.reference_number} · {review.signed_date}</p>}{review.evidence_url && <a className="mt-2 inline-block text-sm font-semibold text-[#015850]" href={review.evidence_url}>Unduh bukti privat</a>}</li>)}</ol>{!session.reviews.length && <p className="mt-3 text-sm text-slate-500">Belum ada review.</p>}</section>
    </AuthenticatedLayout>;
}
