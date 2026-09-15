import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { FormEventHandler } from 'react';

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

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Register Aset Baru
                </h2>
            }
        >
            <Head title="Register Aset" />

            <div className="py-12">
                <div className="mx-auto max-w-4xl sm:px-6 lg:px-8">
                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg p-6">
                        <form onSubmit={submit} className="space-y-6">
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-4">
                                    <h3 className="text-lg font-medium text-dark border-b pb-2">Informasi Dasar</h3>
                                    
                                    <div>
                                        <InputLabel htmlFor="name" value="Nama Aset *" />
                                        <TextInput
                                            id="name"
                                            className="mt-1 block w-full"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                        />
                                        <InputError className="mt-2" message={errors.name} />
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="category_id" value="Kategori *" />
                                        <select
                                            id="category_id"
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                            value={data.category_id}
                                            onChange={(e) => setData('category_id', e.target.value)}
                                            required
                                        >
                                            <option value="">-- Pilih Kategori --</option>
                                            {categories.map((c: any) => (
                                                <option key={c.id} value={c.id}>{c.name}</option>
                                            ))}
                                        </select>
                                        <InputError className="mt-2" message={errors.category_id} />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="item_code" value="Kode Barang" />
                                            <TextInput
                                                id="item_code"
                                                className="mt-1 block w-full"
                                                value={data.item_code}
                                                onChange={(e) => setData('item_code', e.target.value)}
                                            />
                                            <InputError className="mt-2" message={errors.item_code} />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="nup" value="NUP" />
                                            <TextInput
                                                id="nup"
                                                className="mt-1 block w-full"
                                                value={data.nup}
                                                onChange={(e) => setData('nup', e.target.value)}
                                            />
                                            <InputError className="mt-2" message={errors.nup} />
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <InputLabel htmlFor="brand_type" value="Merek / Tipe" />
                                        <TextInput
                                            id="brand_type"
                                            className="mt-1 block w-full"
                                            value={data.brand_type}
                                            onChange={(e) => setData('brand_type', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.brand_type} />
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <h3 className="text-lg font-medium text-dark border-b pb-2">Detail & Lokasi</h3>
                                    
                                    <div>
                                        <InputLabel htmlFor="value" value="Nilai Perolehan (Rp)" />
                                        <TextInput
                                            id="value"
                                            type="number"
                                            className="mt-1 block w-full"
                                            value={data.value}
                                            onChange={(e) => setData('value', e.target.value)}
                                        />
                                        <InputError className="mt-2" message={errors.value} />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="condition" value="Kondisi *" />
                                            <select
                                                id="condition"
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                                value={data.condition}
                                                onChange={(e) => setData('condition', e.target.value)}
                                                required
                                            >
                                                <option value="Baik">Baik</option>
                                                <option value="Rusak Ringan">Rusak Ringan</option>
                                                <option value="Rusak Berat">Rusak Berat</option>
                                            </select>
                                            <InputError className="mt-2" message={errors.condition} />
                                        </div>
                                        <div>
                                            <InputLabel htmlFor="room_id" value="Ruangan/Lokasi" />
                                            <select
                                                id="room_id"
                                                className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary"
                                                value={data.room_id}
                                                onChange={(e) => setData('room_id', e.target.value)}
                                            >
                                                <option value="">-- Pilih Ruangan --</option>
                                                {rooms.map((r: any) => (
                                                    <option key={r.id} value={r.id}>{r.name}</option>
                                                ))}
                                            </select>
                                            <InputError className="mt-2" message={errors.room_id} />
                                        </div>
                                    </div>

                                    <div>
                                        <InputLabel htmlFor="images" value="Foto Aset (Max 10MB)" />
                                        <input
                                            type="file"
                                            id="images"
                                            multiple
                                            accept="image/*"
                                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-white hover:file:bg-opacity-90"
                                            onChange={(e) => {
                                                if (e.target.files) {
                                                    setData('images', Array.from(e.target.files));
                                                }
                                            }}
                                        />
                                        <p className="mt-1 text-xs text-gray-500">Foto akan otomatis dikonversi ke format WebP teroptimasi.</p>
                                        <InputError className="mt-2" message={errors.images} />
                                    </div>
                                </div>
                            </div>

                            <div className="flex items-center justify-end border-t pt-4 mt-6">
                                <Link
                                    href={route('assets.index')}
                                    className="mr-4 text-sm text-gray-600 underline hover:text-gray-900"
                                >
                                    Batal
                                </Link>
                                <PrimaryButton disabled={processing}>
                                    Simpan Aset
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
