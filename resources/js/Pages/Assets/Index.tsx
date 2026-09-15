import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';

export default function Index({ auth, assets, filters }: any) {
    return (
        <AuthenticatedLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800">
                        Katalog Aset BMN
                    </h2>
                    <Link
                        href={route('assets.create')}
                        className="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-opacity-90 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                    >
                        + Register Aset
                    </Link>
                </div>
            }
        >
            <Head title="Katalog Aset" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                        
                        <div className="mb-6 flex gap-4">
                            <input
                                type="text"
                                placeholder="Cari aset, NUP, kode..."
                                className="w-full sm:w-1/3 rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                defaultValue={filters?.search}
                            />
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
                            {assets.data.length === 0 && (
                                <div className="col-span-full text-center text-gray-500 py-10">
                                    Belum ada aset terdaftar.
                                </div>
                            )}
                            {assets.data.map((asset: any) => (
                                <div key={asset.id} className="border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition bg-white flex flex-col">
                                    <div className="h-48 bg-gray-100 flex items-center justify-center overflow-hidden">
                                        {asset.media?.length > 0 ? (
                                            <img src={asset.media[0].thumbnail_path} alt={asset.name} className="w-full h-full object-cover" />
                                        ) : (
                                            <svg className="w-12 h-12 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        )}
                                    </div>
                                    <div className="p-4 flex-1 flex flex-col">
                                        <div className="text-xs text-info mb-1 font-semibold tracking-wide">
                                            {asset.item_code || 'KODE-?'} &bull; NUP {asset.nup || '?'}
                                        </div>
                                        <h3 className="font-bold text-dark mb-1 line-clamp-2">{asset.name}</h3>
                                        <div className="text-sm text-gray-500 mb-4 truncate">{asset.brand_type || '-'}</div>
                                        
                                        <div className="mt-auto flex justify-between items-center text-xs">
                                            <span className={`px-2 py-1 rounded-md font-medium ${asset.condition === 'Baik' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>
                                                {asset.condition}
                                            </span>
                                            <span className="font-bold text-gray-700">
                                                Rp {new Intl.NumberFormat('id-ID').format(asset.value || 0)}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>

                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
