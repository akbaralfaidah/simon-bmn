import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import { Search, Plus, Image as ImageIcon, MapPin, Tag, PackageSearch } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function Index({ auth, assets, filters }: any) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 tracking-tight">
                        Katalog Aset BMN
                    </h2>
                    <Link
                        href={route('assets.create')}
                        className="inline-flex items-center gap-2 rounded-xl bg-brand-primary px-4 py-2.5 text-sm font-semibold text-white shadow-md shadow-brand-primary/20 hover:bg-brand-primaryLight hover:-translate-y-0.5 transition-all duration-200"
                    >
                        <Plus className="h-4 w-4" />
                        Registrasi Aset
                    </Link>
                </div>
            }
        >
            <Head title="Katalog Aset" />

            <div className="max-w-7xl mx-auto space-y-6">
                {/* Search Bar */}
                <div className="bg-white p-4 rounded-2xl shadow-sm border border-gray-100 flex gap-4">
                    <div className="relative flex-1 max-w-xl">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <Search className="h-5 w-5 text-gray-400" />
                        </div>
                        <input
                            type="text"
                            placeholder="Cari nama aset, NUP, kode barang..."
                            className="block w-full pl-10 pr-3 py-2.5 border border-gray-200 rounded-xl leading-5 bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-primary/20 focus:border-brand-primary transition-colors sm:text-sm"
                            defaultValue={filters?.search}
                        />
                    </div>
                </div>

                {/* Grid */}
                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    {assets.data.length === 0 && (
                        <div className="col-span-full bg-white rounded-2xl border border-dashed border-gray-300 p-12 text-center">
                            <PackageSearch className="mx-auto h-12 w-12 text-gray-300 mb-4" />
                            <h3 className="text-lg font-medium text-gray-900">Belum ada aset terdaftar</h3>
                            <p className="text-gray-500 mt-1">Mulai dengan meregistrasikan aset BMN pertama Anda.</p>
                        </div>
                    )}
                    
                    {assets.data.map((asset: any) => (
                        <div key={asset.id} className="group bg-white rounded-2xl overflow-hidden shadow-sm border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-1 flex flex-col">
                            {/* Image Container */}
                            <div className="h-48 bg-gray-100 relative overflow-hidden">
                                {asset.media?.length > 0 ? (
                                    <img src={asset.media[0].thumbnail_path} alt={asset.name} className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                ) : (
                                    <div className="w-full h-full flex items-center justify-center bg-gray-50">
                                        <ImageIcon className="w-12 h-12 text-gray-300" />
                                    </div>
                                )}
                                <div className="absolute top-3 right-3">
                                    <span className={cn(
                                        "px-2.5 py-1 rounded-full text-[10px] font-bold tracking-wide shadow-sm",
                                        asset.condition === 'Baik' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                    )}>
                                        {asset.condition?.toUpperCase() || 'UNKNOWN'}
                                    </span>
                                </div>
                            </div>

                            {/* Content */}
                            <div className="p-5 flex-1 flex flex-col">
                                <div className="flex items-center gap-1.5 text-xs text-brand-informative font-semibold mb-2 bg-brand-informative/10 w-fit px-2 py-0.5 rounded-md">
                                    <Tag className="h-3 w-3" />
                                    {asset.item_code || 'KODE-?'} &bull; NUP {asset.nup || '?'}
                                </div>
                                <h3 className="font-bold text-gray-900 mb-1 line-clamp-2 leading-tight group-hover:text-brand-primary transition-colors">
                                    {asset.name}
                                </h3>
                                <div className="flex items-center gap-1.5 text-sm text-gray-500 mb-4">
                                    <MapPin className="h-3.5 w-3.5" />
                                    <span className="truncate">{asset.brand_type || 'Tanpa Merk'}</span>
                                </div>
                                
                                <div className="mt-auto pt-4 border-t border-gray-50 flex justify-between items-center">
                                    <div className="text-[10px] text-gray-400 font-medium uppercase tracking-wider">Nilai Perolehan</div>
                                    <span className="font-bold text-brand-primaryDark">
                                        Rp {new Intl.NumberFormat('id-ID').format(asset.value || 0)}
                                    </span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
