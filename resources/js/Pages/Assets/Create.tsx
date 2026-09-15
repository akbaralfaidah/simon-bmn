import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import { FormEventHandler } from 'react';
import { Save, ArrowLeft, Package, MapPin, Camera } from 'lucide-react';

export default function Create({ auth, categories, rooms }: any) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        category_id: '',
        room_id: '',
        nup: '',
        item_code: '',
        brand_type: '',
        serial_number: '',
        specification: '',
        acquisition_date: '',
        value: '',
        condition: 'Baik',
        images: [] as File[],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('assets.store'));
    };

    const inputClass = "block w-full py-2.5 px-3 border border-gray-200 rounded-xl bg-gray-50 placeholder-gray-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-primary/20 focus:border-brand-primary transition-colors text-sm";
    const selectClass = "block w-full py-2.5 px-3 border border-gray-200 rounded-xl bg-gray-50 focus:outline-none focus:bg-white focus:ring-2 focus:ring-brand-primary/20 focus:border-brand-primary transition-colors text-sm appearance-none";
    const labelClass = "block text-sm font-medium text-gray-700 mb-1.5";

    return (
        <AuthenticatedLayout header="Registrasi Aset Baru">
            <Head title="Registrasi Aset" />

            <div className="max-w-5xl mx-auto">
                <div className="mb-6">
                    <Link
                        href={route('assets.index')}
                        className="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-brand-primary transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Kembali ke Katalog
                    </Link>
                </div>

                <form onSubmit={submit}>
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Card 1: Informasi Dasar */}
                        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div className="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                                <div className="h-10 w-10 rounded-xl bg-brand-primary/10 flex items-center justify-center">
                                    <Package className="h-5 w-5 text-brand-primary" />
                                </div>
                                <h3 className="text-lg font-bold text-gray-900">Informasi Dasar</h3>
                            </div>

                            <div className="space-y-4">
                                <div>
                                    <label htmlFor="name" className={labelClass}>Nama Aset <span className="text-red-500">*</span></label>
                                    <input id="name" className={inputClass} value={data.name} onChange={(e) => setData('name', e.target.value)} required placeholder="cth: Laptop Dell Latitude 5520" />
                                    <InputError className="mt-1.5" message={errors.name} />
                                </div>

                                <div>
                                    <label htmlFor="category_id" className={labelClass}>Kategori <span className="text-red-500">*</span></label>
                                    <select id="category_id" className={selectClass} value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} required>
                                        <option value="">-- Pilih Kategori --</option>
                                        {categories.map((c: any) => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>
                                    <InputError className="mt-1.5" message={errors.category_id} />
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <label htmlFor="item_code" className={labelClass}>Kode Barang</label>
                                        <input id="item_code" className={inputClass} value={data.item_code} onChange={(e) => setData('item_code', e.target.value)} placeholder="3.06.02.01.003" />
                                        <InputError className="mt-1.5" message={errors.item_code} />
                                    </div>
                                    <div>
                                        <label htmlFor="nup" className={labelClass}>NUP</label>
                                        <input id="nup" className={inputClass} value={data.nup} onChange={(e) => setData('nup', e.target.value)} placeholder="001" />
                                        <InputError className="mt-1.5" message={errors.nup} />
                                    </div>
                                </div>

                                <div>
                                    <label htmlFor="brand_type" className={labelClass}>Merek / Tipe</label>
                                    <input id="brand_type" className={inputClass} value={data.brand_type} onChange={(e) => setData('brand_type', e.target.value)} placeholder="Dell Latitude 5520" />
                                    <InputError className="mt-1.5" message={errors.brand_type} />
                                </div>

                                <div>
                                    <label htmlFor="serial_number" className={labelClass}>Nomor Seri</label>
                                    <input id="serial_number" className={inputClass} value={data.serial_number} onChange={(e) => setData('serial_number', e.target.value)} placeholder="SN-XXXX-YYYY" />
                                </div>
                            </div>
                        </div>

                        {/* Card 2: Detail & Lokasi */}
                        <div className="space-y-6">
                            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                                <div className="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                                    <div className="h-10 w-10 rounded-xl bg-brand-informative/10 flex items-center justify-center">
                                        <MapPin className="h-5 w-5 text-brand-informative" />
                                    </div>
                                    <h3 className="text-lg font-bold text-gray-900">Detail & Lokasi</h3>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <label htmlFor="value" className={labelClass}>Nilai Perolehan (Rp)</label>
                                        <input id="value" type="number" className={inputClass} value={data.value} onChange={(e) => setData('value', e.target.value)} placeholder="15000000" />
                                        <InputError className="mt-1.5" message={errors.value} />
                                    </div>

                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label htmlFor="condition" className={labelClass}>Kondisi <span className="text-red-500">*</span></label>
                                            <select id="condition" className={selectClass} value={data.condition} onChange={(e) => setData('condition', e.target.value)} required>
                                                <option value="Baik">Baik</option>
                                                <option value="Rusak Ringan">Rusak Ringan</option>
                                                <option value="Rusak Berat">Rusak Berat</option>
                                            </select>
                                            <InputError className="mt-1.5" message={errors.condition} />
                                        </div>
                                        <div>
                                            <label htmlFor="room_id" className={labelClass}>Ruangan</label>
                                            <select id="room_id" className={selectClass} value={data.room_id} onChange={(e) => setData('room_id', e.target.value)}>
                                                <option value="">-- Pilih --</option>
                                                {rooms.map((r: any) => (
                                                    <option key={r.id} value={r.id}>{r.name}</option>
                                                ))}
                                            </select>
                                            <InputError className="mt-1.5" message={errors.room_id} />
                                        </div>
                                    </div>

                                    <div>
                                        <label htmlFor="acquisition_date" className={labelClass}>Tanggal Perolehan</label>
                                        <input id="acquisition_date" type="date" className={inputClass} value={data.acquisition_date} onChange={(e) => setData('acquisition_date', e.target.value)} />
                                    </div>
                                </div>
                            </div>

                            {/* Card 3: Upload Foto */}
                            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                                <div className="flex items-center gap-3 mb-5 pb-4 border-b border-gray-100">
                                    <div className="h-10 w-10 rounded-xl bg-brand-secondary/10 flex items-center justify-center">
                                        <Camera className="h-5 w-5 text-brand-secondary" />
                                    </div>
                                    <h3 className="text-lg font-bold text-gray-900">Foto Aset</h3>
                                </div>

                                <div className="border-2 border-dashed border-gray-200 rounded-xl p-6 text-center hover:border-brand-primary/40 transition-colors">
                                    <Camera className="mx-auto h-10 w-10 text-gray-300 mb-3" />
                                    <input
                                        type="file"
                                        id="images"
                                        multiple
                                        accept="image/*"
                                        className="hidden"
                                        onChange={(e) => { if (e.target.files) setData('images', Array.from(e.target.files)); }}
                                    />
                                    <label htmlFor="images" className="cursor-pointer">
                                        <span className="text-sm font-semibold text-brand-primary hover:text-brand-primaryLight">Pilih foto</span>
                                        <span className="text-sm text-gray-500"> atau seret ke sini</span>
                                    </label>
                                    <p className="text-xs text-gray-400 mt-1.5">Otomatis dikonversi ke format WebP. Maks 10MB per file.</p>
                                </div>
                                {data.images.length > 0 && (
                                    <p className="text-xs text-green-600 mt-2 font-medium">{data.images.length} file dipilih</p>
                                )}
                                <InputError className="mt-1.5" message={errors.images} />
                            </div>
                        </div>
                    </div>

                    {/* Submit */}
                    <div className="flex items-center justify-end gap-4 mt-6">
                        <Link href={route('assets.index')} className="px-5 py-2.5 text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors">
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 px-6 py-2.5 bg-brand-primary text-white text-sm font-semibold rounded-xl shadow-lg shadow-brand-primary/20 hover:bg-brand-primaryLight hover:-translate-y-0.5 transition-all duration-200 disabled:opacity-50"
                        >
                            <Save className="h-4 w-4" />
                            {processing ? 'Menyimpan...' : 'Simpan Aset'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
