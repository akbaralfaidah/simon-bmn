import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import TextInput from '@/Components/TextInput';
import { Transition } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import {
    ChangeEvent,
    FormEventHandler,
    MouseEvent as ReactMouseEvent,
    TouchEvent as ReactTouchEvent,
    useEffect,
    useRef,
    useState,
} from 'react';
import {
    PenTool,
    Lock,
    ZoomIn,
    ZoomOut,
    Move,
    Maximize2,
    Sparkles,
    Trash2,
    Upload,
    CheckCircle2,
    RotateCcw,
    Sliders,
    X,
    Bold,
} from 'lucide-react';

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
    const [zoom, setZoom] = useState<number>(1);
    const [panX, setPanX] = useState<number>(0);
    const [panY, setPanY] = useState<number>(0);
    const [boldness, setBoldness] = useState<number>(0);
    const [imageTimestamp, setImageTimestamp] = useState<number>(Date.now());
    
    // Drag/Pan interaction
    const [isDragging, setIsDragging] = useState(false);
    const [dragStart, setDragStart] = useState({ x: 0, y: 0 });

    const fileInputRef = useRef<HTMLInputElement | null>(null);
    const passwordInput = useRef<HTMLInputElement>(null);
    const deletePasswordInput = useRef<HTMLInputElement>(null);
    const canvasPreviewRef = useRef<HTMLCanvasElement | null>(null);

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
                setOriginalImage(img);
                setZoom(1);
                setPanX(0);
                setPanY(0);
                setBoldness(0);
                setThreshold(215);
                setIsModalOpen(true);
            };
            img.src = event.target?.result as string;
        };
        reader.readAsDataURL(file);
    };

    /**
     * Auto-detect ink bounding box and scale/center inside 1080x1080 canvas
     */
    const handleAutoFit = () => {
        if (!originalImage) return;

        const scanCanvas = document.createElement('canvas');
        const maxScanDim = 800;
        let scaleDown = 1;
        if (originalImage.width > maxScanDim || originalImage.height > maxScanDim) {
            scaleDown = Math.min(maxScanDim / originalImage.width, maxScanDim / originalImage.height);
        }
        scanCanvas.width = Math.round(originalImage.width * scaleDown);
        scanCanvas.height = Math.round(originalImage.height * scaleDown);
        const scanCtx = scanCanvas.getContext('2d');
        if (!scanCtx) return;

        scanCtx.drawImage(originalImage, 0, 0, scanCanvas.width, scanCanvas.height);
        const imgData = scanCtx.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
        const d = imgData.data;

        let minX = scanCanvas.width;
        let minY = scanCanvas.height;
        let maxX = 0;
        let maxY = 0;
        let foundInk = false;

        for (let y = 0; y < scanCanvas.height; y++) {
            for (let x = 0; x < scanCanvas.width; x++) {
                const i = (y * scanCanvas.width + x) * 4;
                const lum = 0.299 * d[i] + 0.587 * d[i + 1] + 0.114 * d[i + 2];
                if (lum < threshold) {
                    foundInk = true;
                    if (x < minX) minX = x;
                    if (x > maxX) maxX = x;
                    if (y < minY) minY = y;
                    if (y > maxY) maxY = y;
                }
            }
        }

        if (!foundInk || maxX <= minX || maxY <= minY) {
            setZoom(1);
            setPanX(0);
            setPanY(0);
            return;
        }

        // Real image coordinates of ink
        const realMinX = minX / scaleDown;
        const realMaxX = maxX / scaleDown;
        const realMinY = minY / scaleDown;
        const realMaxY = maxY / scaleDown;

        const inkWidth = realMaxX - realMinX;
        const inkHeight = realMaxY - realMinY;
        const inkCenterX = (realMinX + realMaxX) / 2;
        const inkCenterY = (realMinY + realMaxY) / 2;

        // Target: fill ~80% of 1080x1080 canvas
        const targetDim = 1080 * 0.82;
        const calculatedScale = Math.min(targetDim / inkWidth, targetDim / inkHeight);
        const clampedZoom = Math.max(0.6, Math.min(3.5, Number(calculatedScale.toFixed(2))));

        // Calculate offset so inkCenter matches 540,540
        const calculatedPanX = Math.round(540 - inkCenterX * clampedZoom);
        const calculatedPanY = Math.round(540 - inkCenterY * clampedZoom);

        setZoom(clampedZoom);
        setPanX(calculatedPanX);
        setPanY(calculatedPanY);
    };

    /**
     * Render the signature at 1080x1080 resolution with background removal & boldness
     */
    useEffect(() => {
        if (!originalImage || !isModalOpen) return;

        const outputCanvas = document.createElement('canvas');
        outputCanvas.width = 1080;
        outputCanvas.height = 1080;
        const ctx = outputCanvas.getContext('2d');
        if (!ctx) return;

        // 1. Draw scaled and panned image on temporary canvas
        const tempCanvas = document.createElement('canvas');
        tempCanvas.width = 1080;
        tempCanvas.height = 1080;
        const tempCtx = tempCanvas.getContext('2d');
        if (!tempCtx) return;

        // Compute draw position: if pan is 0, center the image
        const drawW = originalImage.width * zoom;
        const drawH = originalImage.height * zoom;
        const drawX = panX !== 0 ? panX : (1080 - drawW) / 2;
        const drawY = panY !== 0 ? panY : (1080 - drawH) / 2;

        tempCtx.fillStyle = '#ffffff';
        tempCtx.fillRect(0, 0, 1080, 1080);
        tempCtx.drawImage(originalImage, drawX, drawY, drawW, drawH);

        // 2. Remove background based on luminance threshold
        const imgData = tempCtx.getImageData(0, 0, 1080, 1080);
        const d = imgData.data;

        for (let i = 0; i < d.length; i += 4) {
            const r = d[i];
            const g = d[i + 1];
            const b = d[i + 2];
            const lum = 0.299 * r + 0.587 * g + 0.114 * b;

            if (lum > threshold) {
                d[i + 3] = 0; // transparent
            } else {
                if (lum > threshold - 16) {
                    const alphaRatio = 1 - (lum - (threshold - 16)) / 16;
                    d[i + 3] = Math.round(255 * alphaRatio);
                } else {
                    d[i + 3] = 255;
                }
                // Crisp dark ink
                d[i] = Math.max(0, r - 30);
                d[i + 1] = Math.max(0, g - 30);
                d[i + 2] = Math.max(0, b - 30);
            }
        }
        tempCtx.putImageData(imgData, 0, 0);

        // 3. Apply boldness / dilation if enabled
        ctx.clearRect(0, 0, 1080, 1080);
        if (boldness === 0) {
            ctx.drawImage(tempCanvas, 0, 0);
        } else {
            const offsets: [number, number][] = [];
            const r = boldness;
            for (let dx = -r; dx <= r; dx++) {
                for (let dy = -r; dy <= r; dy++) {
                    if (dx * dx + dy * dy <= r * r) {
                        offsets.push([dx, dy]);
                    }
                }
            }
            for (const [ox, oy] of offsets) {
                ctx.drawImage(tempCanvas, ox, oy);
            }
        }

        const dataUrl = outputCanvas.toDataURL('image/png');
        setProcessedDataUrl(dataUrl);
        setData('signature_image', dataUrl);

        // Draw onto interactive preview canvas
        if (canvasPreviewRef.current) {
            const prevCtx = canvasPreviewRef.current.getContext('2d');
            if (prevCtx) {
                prevCtx.clearRect(0, 0, canvasPreviewRef.current.width, canvasPreviewRef.current.height);
                prevCtx.drawImage(outputCanvas, 0, 0, canvasPreviewRef.current.width, canvasPreviewRef.current.height);
            }
        }
    }, [originalImage, isModalOpen, threshold, zoom, panX, panY, boldness]);

    // Mouse / Touch Drag handlers for Pan
    const handleMouseDown = (e: ReactMouseEvent<HTMLCanvasElement>) => {
        setIsDragging(true);
        setDragStart({ x: e.clientX, y: e.clientY });
    };

    const handleMouseMove = (e: ReactMouseEvent<HTMLCanvasElement>) => {
        if (!isDragging) return;
        const dx = e.clientX - dragStart.x;
        const dy = e.clientY - dragStart.y;
        const scaleRatio = 1080 / (canvasPreviewRef.current?.width || 280);
        setPanX((prev) => Math.round(prev + dx * scaleRatio));
        setPanY((prev) => Math.round(prev + dy * scaleRatio));
        setDragStart({ x: e.clientX, y: e.clientY });
    };

    const handleMouseUp = () => {
        setIsDragging(false);
    };

    const handleTouchStart = (e: ReactTouchEvent<HTMLCanvasElement>) => {
        if (e.touches.length === 1) {
            setIsDragging(true);
            setDragStart({ x: e.touches[0].clientX, y: e.touches[0].clientY });
        }
    };

    const handleTouchMove = (e: ReactTouchEvent<HTMLCanvasElement>) => {
        if (!isDragging || e.touches.length !== 1) return;
        const dx = e.touches[0].clientX - dragStart.x;
        const dy = e.touches[0].clientY - dragStart.y;
        const scaleRatio = 1080 / (canvasPreviewRef.current?.width || 280);
        setPanX((prev) => Math.round(prev + dx * scaleRatio));
        setPanY((prev) => Math.round(prev + dy * scaleRatio));
        setDragStart({ x: e.touches[0].clientX, y: e.touches[0].clientY });
    };

    const handleTouchEnd = () => {
        setIsDragging(false);
    };

    const resetAdjustments = () => {
        setZoom(1);
        setPanX(0);
        setPanY(0);
        setBoldness(0);
        setThreshold(215);
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
        resetAdjustments();
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
                    Unggah tanda tangan resmi Anda untuk pembubuhan otomatis pada dokumen Berita Acara Serah Terima (BAST). Anda dapat mengatur ukuran, posisi, ketebalan tinta, dan transparansi latar belakang.
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
                                    className="simon-button-secondary cursor-pointer text-xs inline-flex items-center gap-1.5"
                                >
                                    <PenTool className="w-3.5 h-3.5" />
                                    Perbarui Tanda Tangan
                                </label>
                                <button
                                    type="button"
                                    onClick={() => setIsDeleteModalOpen(true)}
                                    className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors"
                                >
                                    <Trash2 className="w-3.5 h-3.5" />
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
                                <p>• Format PNG transparan standar 1080x1080 proporsional.</p>
                                <p>• Siap dibubuhkan pada dokumen BAST (.docx / PDF).</p>
                            </div>
                        </div>
                    </div>
                ) : (
                    <div className="rounded-xl border-2 border-dashed border-slate-300 p-6 text-center hover:border-primary/50 transition-colors">
                        <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-50 text-primary">
                            <PenTool className="h-6 w-6" />
                        </div>
                        <h3 className="mt-3 text-sm font-semibold text-slate-800">
                            Belum Ada Tanda Tangan Tersimpan
                        </h3>
                        <p className="mt-1 text-xs text-slate-500 max-w-md mx-auto">
                            Foto atau scan tanda tangan Anda di atas kertas putih bersih, lalu pilih berkasnya di sini. Anda dapat mengatur ukuran, posisi, ketebalan, dan pembersihan latar secara interaktif.
                        </p>
                        <div className="mt-4">
                            <label
                                htmlFor="signature-file-input"
                                className="inline-flex cursor-pointer items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-primary-700 active:scale-[0.98] transition-all"
                            >
                                <Upload className="w-4 h-4" />
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
                <div className="mt-3 flex items-center gap-1.5 text-sm text-emerald-600 font-medium">
                    <CheckCircle2 className="w-4 h-4" />
                    <span>Tanda tangan digital berhasil diperbarui.</span>
                </div>
            </Transition>

            {/* Modal Upload & Advanced Canvas Editor */}
            <Modal show={isModalOpen} onClose={closeModal} maxWidth="2xl">
                <form onSubmit={submitSignature} className="p-6">
                    <div className="flex items-center justify-between border-b border-slate-200 pb-3">
                        <div className="flex items-center gap-2">
                            <Sliders className="w-5 h-5 text-primary" />
                            <h3 className="text-lg font-bold text-slate-900">
                                Editor Tanda Tangan Digital (1080x1080)
                            </h3>
                        </div>
                        <button
                            type="button"
                            onClick={closeModal}
                            className="rounded-lg p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors"
                        >
                            <X className="w-5 h-5" />
                        </button>
                    </div>

                    <div className="mt-4 grid grid-cols-1 md:grid-cols-12 gap-5">
                        {/* Interactive Canvas Preview Area */}
                        <div className="md:col-span-6 flex flex-col items-center">
                            <div className="w-full flex items-center justify-between mb-2">
                                <span className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                    <Move className="w-3.5 h-3.5 text-primary" />
                                    Kanvas Interaktif (Geser / Drag):
                                </span>
                                <button
                                    type="button"
                                    onClick={handleAutoFit}
                                    className="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:text-primary-700 bg-primary-50 px-2 py-0.5 rounded transition-colors"
                                    title="Paskan dan tengahkan coretan secara otomatis"
                                >
                                    <Sparkles className="w-3 h-3" />
                                    Auto-Fit
                                </button>
                            </div>

                            <div
                                className="relative w-full aspect-square max-w-[320px] rounded-xl border-2 border-slate-300 shadow-inner overflow-hidden flex items-center justify-center select-none cursor-grab active:cursor-grabbing"
                                style={{
                                    backgroundImage:
                                        'linear-gradient(45deg, #f1f5f9 25%, transparent 25%), linear-gradient(-45deg, #f1f5f9 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #f1f5f9 75%), linear-gradient(-45deg, transparent 75%, #f1f5f9 75%)',
                                    backgroundSize: '16px 16px',
                                    backgroundPosition: '0 0, 0 8px, 8px -8px, -8px 0px',
                                }}
                            >
                                <canvas
                                    ref={canvasPreviewRef}
                                    width={320}
                                    height={320}
                                    onMouseDown={handleMouseDown}
                                    onMouseMove={handleMouseMove}
                                    onMouseUp={handleMouseUp}
                                    onMouseLeave={handleMouseUp}
                                    onTouchStart={handleTouchStart}
                                    onTouchMove={handleTouchMove}
                                    onTouchEnd={handleTouchEnd}
                                    className="w-full h-full object-contain"
                                />

                                <div className="absolute bottom-2 left-2 right-2 flex items-center justify-between pointer-events-none">
                                    <span className="bg-slate-900/60 backdrop-blur-xs text-white text-[10px] px-2 py-0.5 rounded-full font-mono">
                                        1080 x 1080 px
                                    </span>
                                    <span className="bg-slate-900/60 backdrop-blur-xs text-white text-[10px] px-2 py-0.5 rounded-full font-mono">
                                        Zoom: {zoom.toFixed(1)}x
                                    </span>
                                </div>
                            </div>
                            <p className="mt-1.5 text-[11px] text-slate-500 text-center">
                                Klik & geser kanvas untuk mengatur posisi tanda tangan tepat di tengah.
                            </p>
                        </div>

                        {/* Controls Column */}
                        <div className="md:col-span-6 space-y-3.5">
                            {/* Zoom Slider */}
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                        <ZoomIn className="w-3.5 h-3.5 text-slate-600" />
                                        Ukuran Tanda Tangan (Zoom):
                                    </label>
                                    <span className="text-xs font-mono font-bold text-primary">
                                        {zoom.toFixed(2)}x
                                    </span>
                                </div>
                                <div className="flex items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setZoom((z) => Math.max(0.5, Number((z - 0.1).toFixed(2))))}
                                        className="p-1 rounded bg-white border border-slate-200 text-slate-600 hover:bg-slate-100"
                                    >
                                        <ZoomOut className="w-3.5 h-3.5" />
                                    </button>
                                    <input
                                        type="range"
                                        min={0.5}
                                        max={3.5}
                                        step={0.05}
                                        value={zoom}
                                        onChange={(e) => setZoom(Number(e.target.value))}
                                        className="w-full h-1.5 bg-slate-300 rounded-lg appearance-none cursor-pointer accent-primary"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setZoom((z) => Math.min(3.5, Number((z + 0.1).toFixed(2))))}
                                        className="p-1 rounded bg-white border border-slate-200 text-slate-600 hover:bg-slate-100"
                                    >
                                        <ZoomIn className="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>

                            {/* Boldness / Thickness Slider */}
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                        <Bold className="w-3.5 h-3.5 text-slate-600" />
                                        Tebalkan Coretan (Pena Tipis):
                                    </label>
                                    <span className="text-xs font-mono font-bold text-primary">
                                        {boldness === 0 ? 'Asli' : `+${boldness}px`}
                                    </span>
                                </div>
                                <input
                                    type="range"
                                    min={0}
                                    max={3}
                                    step={1}
                                    value={boldness}
                                    onChange={(e) => setBoldness(Number(e.target.value))}
                                    className="w-full h-1.5 bg-slate-300 rounded-lg appearance-none cursor-pointer accent-primary"
                                />
                                <div className="flex justify-between text-[10px] text-slate-500">
                                    <span>Normal</span>
                                    <span>Sedang (+1)</span>
                                    <span>Tebal (+2)</span>
                                    <span>Ekstra (+3)</span>
                                </div>
                            </div>

                            {/* Threshold Slider */}
                            <div className="rounded-lg border border-slate-200 bg-slate-50 p-3 space-y-1.5">
                                <div className="flex items-center justify-between">
                                    <label className="text-xs font-semibold text-slate-700 flex items-center gap-1.5">
                                        <Sparkles className="w-3.5 h-3.5 text-slate-600" />
                                        Sensitivitas Latar Kertas:
                                    </label>
                                    <span className="text-xs font-mono font-medium text-slate-600">
                                        {threshold}
                                    </span>
                                </div>
                                <input
                                    type="range"
                                    min={160}
                                    max={245}
                                    value={threshold}
                                    onChange={(e) => setThreshold(Number(e.target.value))}
                                    className="w-full h-1.5 bg-slate-300 rounded-lg appearance-none cursor-pointer accent-primary"
                                />
                                <p className="text-[11px] text-slate-500">
                                    Kiri: pertahankan garis halus. Kanan: hilangkan bayangan kertas kusam.
                                </p>
                            </div>

                            {/* Reset Button */}
                            <div className="flex justify-end">
                                <button
                                    type="button"
                                    onClick={resetAdjustments}
                                    className="inline-flex items-center gap-1 text-xs text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-2.5 py-1 rounded-lg transition-colors"
                                >
                                    <RotateCcw className="w-3 h-3" />
                                    Reset Pengaturan
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Syarat dan Ketentuan Kerahasiaan */}
                    <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50/70 p-3.5 text-xs text-amber-900 space-y-2">
                        <div className="flex items-center gap-2 font-bold text-amber-950">
                            <Lock className="h-4 w-4 shrink-0 text-amber-600" />
                            <span>Syarat & Ketentuan Keamanan Data Tanda Tangan:</span>
                        </div>
                        <ul className="list-disc pl-4 space-y-1 text-amber-900/90 leading-relaxed">
                            <li>
                                Tanda tangan digital ini adalah <strong>data kedinasan privat dan rahasia</strong> yang disimpan pada direktori terisolasi di server internal.
                            </li>
                            <li>
                                Sistem hanya membubuhkan tanda tangan pada dokumen BAST resmi setelah Anda memasukkan dan mengonfirmasi kata sandi akun Anda.
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
                    <div className="mt-4">
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
                        <InputError message={errors.password} className="mt-1" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={closeModal} disabled={processing}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing || !data.agreement}>
                            {processing ? 'Menyimpan...' : 'Simpan Tanda Tangan'}
                        </PrimaryButton>
                    </div>
                </form>
            </Modal>

            {/* Modal Delete Confirmation */}
            <Modal show={isDeleteModalOpen} onClose={closeDeleteModal} maxWidth="md">
                <form onSubmit={submitDeleteSignature} className="p-6">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                            <Trash2 className="h-5 w-5" />
                        </div>
                        <div>
                            <h3 className="text-base font-bold text-slate-900">
                                Hapus Tanda Tangan Digital?
                            </h3>
                            <p className="text-xs text-slate-500">
                                Tindakan ini tidak dapat dibatalkan. Anda harus mengunggah ulang jika ingin membubuhkan tanda tangan pada BAST berikutnya.
                            </p>
                        </div>
                    </div>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="delete-signature-password"
                            value="Masukkan Kata Sandi untuk Konfirmasi Hapus"
                        />
                        <TextInput
                            id="delete-signature-password"
                            ref={deletePasswordInput}
                            type="password"
                            name="password"
                            value={deleteData.password}
                            onChange={(e) => setDeleteData('password', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="Kata sandi akun Anda"
                            required
                        />
                        <InputError message={deleteErrors.password} className="mt-1" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton
                            type="button"
                            onClick={closeDeleteModal}
                            disabled={deleteProcessing}
                        >
                            Batal
                        </SecondaryButton>
                        <DangerButton type="submit" disabled={deleteProcessing}>
                            {deleteProcessing ? 'Menghapus...' : 'Ya, Hapus Tanda Tangan'}
                        </DangerButton>
                    </div>
                </form>
            </Modal>
        </section>
    );
}
