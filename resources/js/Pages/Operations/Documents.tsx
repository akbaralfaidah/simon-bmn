import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ActionForm from '@/Components/ActionForm';
import Pagination from '@/Components/Pagination';
import StatusBadge from '@/Components/StatusBadge';
import { Head } from '@inertiajs/react';

export default function Documents({ documents, templates, templatesApproved }: any) {
    return <AuthenticatedLayout header={<h1 className="text-2xl font-bold">Dokumen & arsip berversi</h1>}><Head title="Dokumen & arsip" />
        {!templatesApproved && <p className="simon-card mb-6 text-sm">Format surat masih draf operasional. Pengesahan kop, nomor, dan pejabat oleh instansi diperlukan sebelum digunakan sebagai surat resmi. Tanda tangan arsip lama tidak disalin ke surat baru.</p>}
        <div className="space-y-4">{documents.data.map((doc: any) => <section className="simon-card" key={doc.id}>
            <div className="flex flex-wrap justify-between gap-3"><h2 className="font-semibold">{templates[doc.bast_type] || doc.bast_type}</h2><StatusBadge status={doc.status} /></div><p className="my-3 break-words text-sm">{doc.bast_number} · Versi {doc.version}{doc.supersedes_id ? ` · Menggantikan dokumen #${doc.supersedes_id}` : ''}</p>{doc.review_notes && <p className="mb-3 text-sm">Catatan: {doc.review_notes}</p>}
            {doc.canView && <div className="flex flex-wrap gap-2"><a className="simon-button-secondary" target="_blank" rel="noreferrer" href={route('documents.show', doc.id)}>Pratinjau / cetak</a>{doc.scan_status === 'clean' && doc.status !== 'draft' && <a className="simon-button-secondary" href={route('documents.show', { document: doc.id, signed: 1 })}>Unduh bukti asli</a>}
                {doc.canUpload && doc.status === 'draft' && <ActionForm title="Unggah PDF bertanda tangan" url={route('documents.action', [doc.id, 'upload'])} fields={[{ name: 'document', label: 'PDF maksimal 10 MB', type: 'file' }]} />}
                {doc.canVerify && doc.status === 'uploaded' && <><ActionForm title="Verifikasi dokumen" url={route('documents.action', [doc.id, 'verify'])} description="Periksa berkas asli, isi dan tanda tangan pihak berwenang. Ini mencatat hasil pemeriksaan, bukan membuat tanda tangan." /><ActionForm title="Kembalikan untuk koreksi" url={route('documents.action', [doc.id, 'reject'])} fields={[{ name: 'reason', label: 'Alasan koreksi', type: 'textarea' }]} /></>}
                {doc.canVerify && ['rejected','verified'].includes(doc.status) && <ActionForm title="Buat versi pengganti" url={route('documents.action', [doc.id, 'replace'])} fields={[{ name: 'reason', label: 'Alasan penggantian', type: 'textarea' }]} />}
            </div>}
        </section>)}</div>{documents.data.length === 0 && <p className="simon-card text-sm">Belum ada dokumen. Buat draf dari transaksi peminjaman, penetapan, inventarisasi, atau laporan terkait.</p>}<Pagination data={documents} />
    </AuthenticatedLayout>;
}
