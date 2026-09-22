import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import Pagination from '@/Components/Pagination';

export default function Index({ assets, filters, canCreate }: any) {
    return (
        <AuthenticatedLayout 
            header={
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 tracking-tight">Katalog Barang BMN</h1>
                        <p className="mt-1 text-xs sm:text-sm text-slate-500">Daftar barang inventaris dalam unit dan cakupan penugasan Anda.</p>
                    </div>
                    {canCreate && (
                        <Link className="simon-button" href={route('assets.create')}>
                            + Registrasi Aset Baru
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Katalog Aset" />
            
            {/* Filter Form */}
            <form className="simon-card mb-6 flex flex-wrap items-end gap-3">
                <div className="min-w-48 flex-1">
                    <label htmlFor="asset-search" className="mb-1.5 block text-xs font-medium text-slate-700">
                        Cari Nama, Kode Barang, atau NUP
                    </label>
                    <input 
                        className="simon-input" 
                        id="asset-search" 
                        name="search" 
                        defaultValue={filters.search || ''} 
                        placeholder="Contoh: laptop, kamera forensik, atau 001" 
                    />
                </div>
                <div>
                    <label htmlFor="asset-condition" className="mb-1.5 block text-xs font-medium text-slate-700">
                        Kondisi
                    </label>
                    <select 
                        className="simon-input" 
                        id="asset-condition" 
                        name="condition" 
                        defaultValue={filters.condition || ''}
                    >
                        <option value="">Semua kondisi</option>
                        {['Baik', 'Rusak Ringan', 'Rusak Berat'].map(value => (
                            <option key={value}>{value}</option>
                        ))}
                    </select>
                </div>
                <button className="simon-button" type="submit">Terapkan Filter</button>
                <Link className="simon-button-secondary" href={route('assets.index')}>Reset</Link>
            </form>

            {/* Asset Grid */}
            <div className="grid grid-cols-2 gap-3 sm:gap-4 md:grid-cols-3 xl:grid-cols-4">
                {assets.data.map((asset: any) => (
                    <Link 
                        key={asset.id} 
                        href={route('assets.show', asset.id)} 
                        className="simon-card block transition-all duration-150 ease-out hover:border-primary/50 hover:shadow-sm active:scale-[0.99]"
                    >
                        <div className="mb-3.5 flex aspect-[16/10] items-center justify-center overflow-hidden rounded-lg bg-slate-100 border border-slate-200/60">
                            {asset.media?.length ? (
                                <img className="h-full w-full object-cover" src={asset.media[0].thumbnail_path} alt={asset.name} loading="lazy" />
                            ) : (
                                <span className="text-xs text-slate-400 font-medium">Foto belum tersedia</span>
                            )}
                        </div>
                        <h2 className="font-semibold text-slate-900 text-sm sm:text-base leading-snug">{asset.name}</h2>
                        <p className="mt-1 font-mono text-[10px] sm:text-xs text-slate-500">{asset.item_code || 'Tanpa kode'} · NUP {asset.nup || '—'}</p>
                        <p className="mt-2.5 text-xs text-slate-600 font-medium flex items-center gap-1.5">
                            <span className="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                            {asset.room?.name || 'Belum ditempatkan'}
                        </p>
                        <div className="mt-3 flex flex-wrap gap-2 pt-2 border-t border-slate-100">
                            <span className="rounded px-2 py-0.5 text-xs font-medium bg-slate-100 text-slate-700">
                                {asset.condition}
                            </span>
                            <span className={`rounded px-2 py-0.5 text-xs font-medium ${
                                asset.status === 'active' && asset.is_loanable 
                                    ? 'bg-primary-50 text-primary' 
                                    : 'bg-slate-100 text-slate-500'
                            }`}>
                                {asset.status === 'active' && asset.is_loanable ? 'Dapat Dipinjam' : 'Tidak Dipinjamkan'}
                            </span>
                        </div>
                    </Link>
                ))}
            </div>

            {!assets.data.length && (
                <div className="simon-card text-center text-slate-500 p-12">
                    <p className="text-sm font-medium text-slate-700">Tidak ada aset yang cocok.</p>
                    <p className="mt-1 text-xs text-slate-400">Coba ubah kata kunci pencarian atau filter kondisi barang.</p>
                </div>
            )}
            
            <div className="mt-6">
                <Pagination data={assets} />
            </div>
        </AuthenticatedLayout>
    );
}
