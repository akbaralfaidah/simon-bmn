import { Head, Link } from '@inertiajs/react';
export default function Error({ status }: { status: number }) {
    const messages: Record<number, string> = { 403: 'Akses ini tidak sesuai penugasan akun Anda.', 404: 'Data atau halaman tidak ditemukan.', 429: 'Terlalu banyak percobaan. Tunggu sebentar sebelum mencoba lagi.', 500: 'Terjadi kendala pada server. Data belum dapat diproses.', 503: 'Layanan sementara tidak tersedia.' };
    return <main className="flex min-h-screen items-center justify-center bg-white p-6"><Head title={'Informasi ' + status} /><section className="simon-card w-full max-w-md text-center"><p className="text-sm font-bold text-[#015850]">SIMON · {status}</p><h1 className="mt-4 text-xl font-bold">Permintaan belum dapat dilanjutkan</h1><p className="my-5 text-sm text-slate-600">{messages[status]}</p><Link className="simon-button" href={route('dashboard')}>Kembali ke beranda</Link></section></main>;
}
