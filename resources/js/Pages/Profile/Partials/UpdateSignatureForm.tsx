import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm, usePage } from '@inertiajs/react';
import { ChangeEvent, FormEventHandler, useEffect, useRef, useState } from 'react';

export default function UpdateSignatureForm({
    hasSignature = false,
    className = '',
}: {
    hasSignature?: boolean;
    className?: string;
}) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isDeleteModalOpen, setIsDeleteModalOpen] = useState(false);
    const [originalImage, setOriginalImage] = useState<HTMLImageElement | null>(null);
    const [processedDataUrl, setProcessedDataUrl] = useState<string>('');
    const [threshold, setThreshold] = useState<number>(215);
    const [imageTimestamp, setImageTimestamp] = useState<number>(Date.now());
    
    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const passwordInput = useRef<HTMLInputElement>(null);
    const deletePasswordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
        recentlySuccessful,
    } = useForm({
        signature_image: '',
        agreement: false,
        password: '',
    });

    const {
        data: deleteData,
        setData: setDeleteData,
        delete: destroy,
        processing: deleteProcessing,
        errors: deleteErrors,
        reset: resetDelete,
        clearErrors: clearDeleteErrors,
    } = useForm({
        password: '',
    });

    const handleFileChange = (e: ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (!file.type.startsWith('image/')) {
            alert('Silakan pilih berkas gambar (PNG, JPG, atau JPEG).');
            return;
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            const img = new Image();
            img.onload = () => {
                // Resize if image is excessively large to keep canvas fast and file size moderate
                const maxDim = 1200;
                let targetW = img.width;
                let targetH = img.height;
                if (targetW > maxDim || targetH > maxDim) {
                    if (targetW > targetH) {
                        targetH = Math.round((targetH * maxDim) / targetW);
                        targetW = maxDim;
                    } else {
                        targetW = Math.round((targetW * maxDim) / targetH);
                        targetH = maxDim;
                    }
                }

                const offCanvas = document.createElement('canvas');
                offCanvas.width = targetW;
                offCanvas.height = targetH;
                const ctx = offCanvas.getContext('2d');
                if (ctx) {
                    ctx.drawImage(img, 0, 0, targetW, targetH);
                    const resizedImg = new Image();
                    resizedImg.onload = () => {
                        setOriginalImage(resizedImg);
                        processBackgroundRemoval(resizedImg, threshold);
                        setIsModalOpen(true);
                    };
                    resizedImg.src = offCanvas.toDataURL('image/png');
                }
            };
            img.src = event.target?.result as string;
        };
        reader.readAsDataURL(file);
    };

    const processBackgroundRemoval = (img: HTMLImageElement, th: number) => {
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        ctx.drawImage(img, 0, 0);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        const d = imageData.data;

        // Process pixels: turn light background to transparent
        for (let i = 0; i < d.length; i += 4) {
            const r = d[i];
            const g = d[i + 1];
            const b = d[i + 2];
            // Standard perceptual luminance
            const lum = 0.299 * r + 0.587 * g + 0.114 * b;

            if (lum > th) {
                // Completely transparent
                d[i + 3] = 0;
            } else {
                // Feather edge slightly for anti-aliasing
                if (lum > th - 18) {
                    const alphaRatio = 1 - (lum - (th - 18)) / 18;
                    d[i + 3] = Math.round(d[i + 3] * alphaRatio);
                }
                // Slightly deepen ink color for clarity
                d[i] = Math.max(0, r - 25);
                d[i + 1] = Math.max(0, g - 25);
                d[i + 2] = Math.max(0, b - 25);
            }
        }

        ctx.putImageData(imageData, 0, 0);
        const dataUrl = canvas.toDataURL('image/png');
        setProcessedDataUrl(dataUrl);
        setData('signature_image', dataUrl);
    };

    const onThresholdChange = (val: number) => {
        setThreshold(val);
        if (originalImage) {
            processBackgroundRemoval(originalImage, val);
        }
    };

    const submitSignature: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('profile.signature.update'), {
            preserveScroll: true,
            onSuccess: () => {
                closeModal();
                setImageTimestamp(Date.now());
            },
            onError: () => {
                passwordInput.current?.focus();
            },
        });
    };

    const closeModal = () => {
        setIsModalOpen(false);
        setOriginalImage(null);
        setProcessedDataUrl('');
        reset();
        clearErrors();
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const submitDeleteSignature: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.signature.destroy'), {
            preserveScroll: true,
            onSuccess: () => {
                closeDeleteModal();
            },
            onError: () => {
                deletePasswordInput.current?.focus();
            },
        });
    };

    const closeDeleteModal = () => {
        setIsDeleteModalOpen(false);
        resetDelete();
        clearDeleteErrors();
    };

    return (
        <section className={className}>
            <header>
                <div className="flex items-center gap-2">
                    <h2 className="text-lg font-medium text-gray-900">
                        Tanda Tangan Digital BAST
                    </h2>
                    <span className="rounded bg-primary-50 px-2 py-0.5 text-xs font-semibold text-primary">
                        Resmi & Rahasia
                    </span>
                </div>

                <p className="mt-1 text-sm text-gray-600">
                    Unggah tanda tangan resmi Anda untuk pembubuhan otomatis pada dokumen Berita Acara Serah Terima (BAST). Sistem akan secara otomatis menghapus latar belakang kertas putih agar tanda tangan berlatar transparan.
                </p>
            </header>

            <div className="mt-6">
                {hasSignature ? (
                    <div className="rounded-xl border border-slate-200 bg-slate-50/50 p-5">
                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                            <div className="space-y-1">
                                <div className="flex items-center gap-2">
                                    <span className="inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
                                    <span className="text-sm font-semibold text-slate-800">
                                        Tanda Tangan Tersimpan & Aktif
                                    </span>
                                </div>
                                <p className="text-xs text-slate-500">
                                    Tanda tangan tersimpan secara privat dan terproteksi. Setiap pembubuhan dokumen BAST tetap memerlukan konfirmasi kata sandi Anda.
                                </p>
                            </div>

                            <div className="flex items-center gap-2">
                                <label
                                    htmlFor="signature-file-input"
                                    className="simon-button-secondary cursor-pointer text-xs"
                                >
                                    Perbarui Tanda Tangan
                                </label>
                                <button
                                    type="button"
                                    onClick={() => setIsDeleteModalOpen(true)}
                                    className="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                >
                                    Hapus
                                </button>
                            </div>
                        </div>

                        {/* Preview Box with subtle grid pattern */}
                        <div className="mt-4 flex flex-col sm:flex-row items-center gap-4">
                            <div
                                className="relative flex h-28 w-56 items-center justify-center rounded-lg border border-slate-200 bg-white p-2 shadow-inner overflow-hidden"
                                style={{
                                    backgroundImage:
                                        'linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%)',
                                    backgroundSize: '16px 16px',
                                    backgroundPosition: '0 0, 0 8px, 8px -8px, -8px 0px',
                                }}
                            >
                                <img
                                    src={`${route('profile.signature')}?t=${imageTimestamp}`}
                                    alt="Pratinjau Tanda Tangan"
                                    className="max-h-full max-w-full object-contain"
                                />
                            </div>
                            <div className="text-xs text-slate-500 space-y-1">
                                <p className="font-medium text-slate-700">Keterangan Format:</p>
                                <p>• Format PNG transparan beresolusi proporsional.</p>
                                <p>• Siap dibubuhkan pada dokumen BAST (.docx / PDF).</p>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="rounded-xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-primary/50 transition-colors">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary">
                            <svg className="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </div>
                        <h3 className="mt-3 text-sm font-semibold text-slate-800">
                            Belum Ada Tanda Tangan Tersimpan
                        </h3>
                        <p className="mt-1 text-xs text-slate-500 max-w-md mx-auto">
                            Foto atau scan tanda tangan Anda di atas kertas putih bersih, lalu pilih berkasnya di sini. Sistem akan menghapus latar belakang secara otomatis.
                        </p>
                        <div className="mt-4">
                            <label
                                htmlFor="signature-file-input"
                                className="inline-flex cursor-pointer items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-primary-700 active:scale-[0.98] transition-all"
                            >
                                Pilih Foto Tanda Tangan
                            </label>
                        </div>
                    </div>
                )}

                <input
                    ref={fileInputRef}
                    id="signature-file-input"
                    type="file"
                    accept="image/png, image/jpeg, image/jpg"
                    className="hidden"
                    onChange={handleFileChange}
                />
            </div>

            <Transition
                show={recentlySuccessful}
                enter="transition ease-in-out"
                enterFrom="opacity-0"
                leave="transition ease-in-out"
                leaveTo="opacity-0"
            >
                <p className="mt-3 text-sm text-emerald-600 font-medium">
                    Tanda tangan digital berhasil diperbarui.
                </p>
            </Transition>

            {/* Modal Upload & Background Removal Processing */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={submitSignature} className="p-6">
                    <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                        <h3 className="text-lg font-bold text-slate-900">
                            Pratinjau & Pembersihan Latar Tanda Tangan
                        </h3>
                        <button
                            type="button"
                            onClick={closeModal}
                            className="rounded text-slate-400 hover:text-slate-600"
                        >
                            ✕
                        </button>
                    </div>

                    <div className="mt-4 space-y-4">
                        <p className="text-xs text-slate-600">
                            Sistem secara otomatis menghapus latar belakang putih kertas. Periksa pratinjau di bawah ini untuk memastikan coretan tanda tangan terlihat bersih dan jelas.
                        </p>

                        {/* Before / After Preview */}
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <span className="block text-xs font-medium text-slate-600 mb-1.5">
                                    Berkas Asli (Sebelum)
                                </span>
                                <div className="h-36 rounded-lg border border-slate-200 bg-white p-2 flex items-center justify-center overflow-hidden">
                                    {originalImage && (
                                        <img
                                            src={originalImage.src}
                                            alt="Asli"
                                            className="max-h-full max-w-full object-contain"
                                        />
                                    )}
                                </div>
                            </div>

                            <div>
                                <span className="block text-xs font-medium text-slate-600 mb-1.5">
                                    Hasil Latar Transparan (Sesudah)
                                </span>
                                <div
                                    className="h-36 rounded-lg border border-slate-200 p-2 flex items-center justify-center overflow-hidden"
                                    style={{
                                        backgroundImage:
                                            'linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%)',
                                        backgroundSize: '16px 16px',
                                        backgroundPosition: '0 0, 0 8px, 8px -8px, -8px 0px',
                                    }}
                                >
                                    {processedDataUrl && (
                                        <img
                                            src={processedDataUrl}
                                            alt="Transparan"
                                            className="max-h-full max-w-full object-contain"
                                        />
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Threshold Slider */}
                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3.5 space-y-2">
                            <div className="flex items-center justify-between">
                                <label
                                    htmlFor="threshold-slider"
                                    className="text-xs font-semibold text-slate-700"
                                >
                                    Sensitivitas Pembersihan Latar:
                                </label>
                                <span className="text-xs font-mono font-medium text-slate-600">
                                    {threshold}
                                </span>
                            </div>
                            <input
                                id="threshold-slider"
                                type="range"
                                min={160}
                                max={245}
                                value={threshold}
                                onChange={(e) => onThresholdChange(Number(e.target.value))}
                                className="w-full h-1.5 bg-slate-300 rounded-lg appearance-none cursor-pointer accent-primary"
                            />
                            <p className="text-[11px] text-slate-500">
                                Geser ke kiri jika coretan tanda tangan terputus. Geser ke kanan jika sisa bayangan kertas masih terlihat.
                            </p>
                        </div>

                        {/* Syarat dan Ketentuan Kerahasiaan */}
                        <div className="rounded-lg border border-amber-200 bg-amber-50/70 p-3.5 text-xs text-amber-900 space-y-2">
                            <div className="flex items-center gap-2 font-bold text-amber-950">
                                <svg className="h-4 w-4 shrink-0 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                                <span>Syarat & Ketentuan Keamanan Data Tanda Tangan:</span>
                            </div>
                            <ul className="list-disc pl-4 space-y-1 text-amber-900/90 leading-relaxed">
                                <li>
                                    Tanda tangan digital ini adalah <strong>data kedinasan privat dan rahasia</strong> yang disimpan pada direktori terisolasi di server internal.
                                </li>
                                <li>
                                    Developer maupun pihak eksternal <strong>tidak memiliki akses langsung</strong> terhadap berkas tanda tangan Anda.
                                </li>
                                <li>
                                    Sistem hanya akan membubuhkan tanda tangan ini pada dokumen BAST resmi setelah Anda memasukkan dan mengonfirmasi kata sandi akun Anda.
                                </li>
                            </ul>
                            <div className="pt-2 border-t border-amber-200">
                                <label className="flex items-start gap-2.5 cursor-pointer select-none">
                                    <input
                                        type="checkbox"
                                        name="agreement"
                                        checked={data.agreement}
                                        onChange={(e) => setData('agreement', e.target.checked)}
                                        className="mt-0.5 h-4 w-4 rounded border-amber-400 text-primary focus:ring-primary/25"
                                        required
                                    />
                                    <span className="font-semibold text-amber-950">
                                        Saya telah membaca, memahami, dan menyetujui ketentuan kerahasiaan tanda tangan digital ini.
                                    </span>
                                </label>
                                <InputError message={errors.agreement} className="mt-1" />
                            </div>
                        </div>

                        {/* Password Confirmation */}
                        <div>
                            <InputLabel
                                htmlFor="signature-password"
                                value="Masukkan Kata Sandi Akun untuk Konfirmasi"
                            />
                            <TextInput
                                id="signature-password"
                                ref={passwordInput}
                                type="password"
                                name="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                className="mt-1 block w-full"
                                placeholder="Kata sandi akun Anda"
                                required
                            />
                            <InputError message={errors.password} className="mt-1.5" />
                        </div>
                    </div>

                    <div className="mt-6 flex justify-end gap-3 border-t border-slate-200 pt-4">
                        <SecondaryButton type="button" onClick={closeModal}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton
                            type="submit"
                            disabled={processing || !data.agreement || !data.password}
                        >
                            {processing ? 'Menyimpan...' : 'Setuju & Simpan Tanda Tangan'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Delete Signature Confirmation */}
            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal}>
                <form onSubmit={submitDeleteSignature} className="p-6">
                    <h3 className="text-lg font-bold text-slate-900">
                        Hapus Tanda Tangan Digital?
                    </h3>
                    <p className="mt-2 text-sm text-slate-600">
                        Tanda tangan yang tersimpan akan dihapus permanen dari server. Anda harus mengunggah kembali jika ingin menandatangani formulir BAST secara digital.
                    </p>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="delete-signature-password"
                            value="Kata Sandi Akun"
                        />
                        <TextInput
                            id="delete-signature-password"
                            ref={deletePasswordInput}
                            type="password"
                            name="password"
                            value={deleteData.password}
                            onChange={(e) => setDeleteData('password', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="Masukkan kata sandi Anda"
                            required
                        />
                        <InputError message={deleteErrors.password} className="mt-1.5" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={closeDeleteModal}>
                            Batal
                        </SecondaryButton>
                        <DangerButton type="submit" disabled={deleteProcessing}>
                            {deleteProcessing ? 'Menghapus...' : 'Hapus Tanda Tangan'}
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
