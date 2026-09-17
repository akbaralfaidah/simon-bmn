import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer, ShieldCheck } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';

export default function QrCode({ asset }: any) {
    const printLabel = () => {
        window.print();
    };

    return (
        <div className="min-h-screen bg-gray-50 p-6 md:p-12 print:p-0 print:bg-white">
            <Head title={`Label QR - ${asset.name}`} />

            <div className="max-w-xl mx-auto">
                <div className="flex items-center justify-between mb-8 print:hidden">
                    <Link
                        href={route('assets.show', asset.id)}
                        className="inline-flex items-center gap-2 text-gray-500 hover:text-gray-900 font-medium transition-colors"
                    >
                        <ArrowLeft className="h-5 w-5" />
                        Kembali ke Detail
                    </Link>
                    <button
                        onClick={printLabel}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-brand-primary text-white rounded-xl font-medium shadow-sm hover:bg-brand-primaryLight transition-colors"
                    >
                        <Printer className="h-4 w-4" />
                        Cetak Label
                    </button>
                </div>

                {/* Print Area */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden print:shadow-none print:border-none print:rounded-none">
                    <div className="p-8">
                        {/* Label Design */}
                        <div className="max-w-sm mx-auto border-2 border-brand-primary rounded-xl overflow-hidden print:max-w-none print:border-4">
                            {/* Header */}
                            <div className="bg-brand-primary text-white p-3 text-center flex flex-col items-center justify-center">
                                <ShieldCheck className="h-6 w-6 mb-1" />
                                <div className="text-xs font-bold uppercase tracking-widest">SIMON</div>
                                <div className="text-[10px] font-medium opacity-90">Sistem Informasi Barang Milik Negara</div>
                            </div>
                            
                            {/* Content */}
                            <div className="p-6 bg-white flex flex-col items-center">
                                <div className="p-2 border-2 border-gray-100 rounded-xl mb-4 bg-white">
                                    <QRCodeSVG 
                                        value={route('assets.show', asset.id)}
                                        size={160}
                                        level="H"
                                        includeMargin={false}
                                    />
                                </div>
                                
                                <div className="text-center w-full">
                                    <div className="font-bold text-gray-900 text-lg mb-1">{asset.item_code || '-'}</div>
                                    <div className="text-xs font-semibold text-gray-500 mb-3 bg-gray-50 py-1 px-3 rounded-full inline-block border border-gray-100">
                                        NUP: {asset.nup || '-'}
                                    </div>
                                    <div className="text-sm font-medium text-gray-900 leading-tight border-t border-dashed border-gray-200 pt-3">
                                        {asset.name}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-8 text-center text-sm text-gray-500 print:hidden">
                            <p>Label ini dioptimalkan untuk ukuran printer label standar.</p>
                            <p>Pastikan pengaturan print tidak menggunakan header/footer bawaan browser.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            {/* Print Styles */}
            <style dangerouslySetInnerHTML={{ __html: `
                @media print {
                    @page { margin: 0; size: 80mm 100mm; }
                    body { background: white; }
                }
            `}} />
        </div>
    );
}
