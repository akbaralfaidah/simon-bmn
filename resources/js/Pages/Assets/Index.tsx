import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';

export default function Index({ assets, filters, canCreate }: any) {
    return <AuthenticatedLayout header={<div className="flex flex-wrap justify-between gap-4"><div><h1 className="text-2xl font-bold">Katalog barang BMN</h1><p className="mt-2 text-sm text-slate-500">Barang dalam unit dan cakupan penugasan Anda.</p></div>{canCreate && <Link className="simon-button" href={route('assets.create')}>Registrasi aset</Link>}</div>}>
        <Head title="Katalog aset" />
        <form className="simon-card mb-6 flex flex-wrap items-end gap-3"><div className="min-w-48 flex-1"><label htmlFor="asset-search" className="mb-2 block text-sm">Cari nama, kode barang, atau NUP</label><input className="simon-input" id="asset-search" name="search" defaultValue={filters.search || ''} placeholder="Contoh: laptop atau 001" /></div><div><label htmlFor="asset-condition" className="mb-2 block text-sm">Kondisi</label><select className="simon-input" id="asset-condition" name="condition" defaultValue={filters.condition || ''}><option value="">Semua kondisi</option>{['Baik', 'Rusak Ringan', 'Rusak Berat'].map(value => <option key={value}>{value}</option>)}</select></div><button className="simon-button">Terapkan</button><Link className="simon-button-secondary" href={route('assets.index')}>Reset</Link></form>
        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">{assets.data.map((asset: any) => <Link key={asset.id} href={route('assets.show', asset.id)} className="simon-card block transition-colors hover:border-[#015850]">
            <div className="mb-4 flex aspect-[16/10] items-center justify-center overflow-hidden rounded-xl bg-slate-50">{asset.media?.length ? <img className="h-full w-full object-cover" src={asset.media[0].thumbnail_path} alt={asset.name} loading="lazy" /> : <span className="text-sm text-slate-400">Foto belum tersedia</span>}</div>
            <h2 className="font-bold">{asset.name}</h2><p className="mt-1 text-xs text-slate-500">{asset.item_code || 'Tanpa kode'} · NUP {asset.nup || '—'}</p><p className="mt-3 text-sm">{asset.room?.name || 'Belum ditempatkan'}</p><div className="mt-3 flex flex-wrap gap-2"><span className="rounded-full bg-slate-100 px-3 py-1 text-xs">{asset.condition}</span><span className="rounded-full bg-[#015850]/5 px-3 py-1 text-xs text-[#015850]">{asset.status === 'active' && asset.is_loanable ? 'Dapat diajukan' : 'Tidak dipinjamkan'}</span></div>
        </Link>)}</div>{!assets.data.length && <p className="simon-card text-center text-slate-500">Tidak ada aset yang cocok. Coba kata kunci atau kondisi lain.</p>}<Pagination data={assets} />
    </AuthenticatedLayout>;
}
