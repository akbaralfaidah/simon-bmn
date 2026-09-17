import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import ActionForm from '@/Components/ActionForm';
import Pagination from '@/Components/Pagination';

export default function Reports({ reports, rooms, categories }: any) {
    return <AuthenticatedLayout header={<h1 className="text-2xl font-bold">Arsip snapshot laporan aset</h1>}>
        <Head title="Snapshot laporan" />
        <Link href={route('workspace.index', 'reports')} className="text-sm font-semibold text-[#015850]">← Laporan aset terkini</Link>
        <section className="simon-card my-6"><h2 className="font-bold">Bekukan data untuk pemeriksaan dan ekspor</h2><p className="my-3 text-sm text-slate-600">Snapshot tidak berubah saat master diperbarui. Label periode bukan rekonstruksi saldo historis; tanggal perekaman dicantumkan pada ekspor. Berkas tetap draf internal, belum pengesahan instansi. Maksimal 1.000 aset per snapshot.</p><ActionForm title="Buat snapshot laporan" url={route('reports.store')} fields={[{ name: 'title', label: 'Judul laporan' }, { name: 'period', label: 'Label periode', type: 'month' }, { name: 'room_id', label: 'Ruangan (kosong = cakupan Anda)', type: 'select', required: false, options: rooms.map((room: any) => ({ value: room.id, label: room.name })) }, { name: 'category_id', label: 'Kategori (opsional)', type: 'select', required: false, options: categories.map((category: any) => ({ value: category.id, label: category.name })) }, { name: 'condition', label: 'Kondisi (opsional)', type: 'select', required: false, options: ['Baik', 'Rusak Ringan', 'Rusak Berat'].map(value => ({ value, label: value })) }]} /></section>
        <div className="space-y-4">{reports.data.map((report: any) => <section key={report.id} className="simon-card"><h2 className="font-semibold">{report.title}</h2><p className="my-3 text-sm text-slate-500">{report.count} aset · Label {report.period} · Direkam {report.created_at}</p><div className="flex flex-wrap gap-3"><a className="simon-button-secondary" href={route('reports.download', [report.id, 'pdf'])}>Unduh PDF</a><a className="simon-button-secondary" href={route('reports.download', [report.id, 'xlsx'])}>Unduh Excel</a></div></section>)}</div>
        {!reports.data.length && <p className="simon-card text-sm text-slate-500">Belum ada snapshot laporan Anda.</p>}<Pagination data={reports} />
    </AuthenticatedLayout>;
}
