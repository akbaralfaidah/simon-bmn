import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';
import { Clock, LogOut } from 'lucide-react';

export default function Pending() {
    return (
        <GuestLayout>
            <Head title="Menunggu Aktivasi" />

            <div className="text-center py-4">
                <div className="mx-auto h-16 w-16 bg-brand-secondary/10 rounded-full flex items-center justify-center mb-5">
                    <Clock className="h-8 w-8 text-brand-secondary" />
                </div>

                <h2 className="text-xl font-bold text-gray-900 mb-2">Menunggu Aktivasi</h2>

                <p className="text-sm text-gray-600 leading-relaxed mb-4">
                    Pendaftaran Anda berhasil! Akun Anda saat ini berstatus <span className="font-semibold text-brand-secondary">menunggu verifikasi</span> dari Koordinator BMN.
                </p>
                <p className="text-sm text-gray-500 leading-relaxed mb-6">
                    Pastikan email sudah terverifikasi, lalu hubungi pengelola akun untuk aktivasi. Setelah diaktifkan, fitur tersedia sesuai role dan cakupan penugasan Anda.
                </p>

                <Link href={route('verification.notice')} className="mb-4 block text-sm font-medium text-brand-primary underline">Periksa verifikasi email / status akun</Link>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-gray-600 bg-gray-100 hover:bg-gray-200 rounded-xl transition-colors"
                >
                    <LogOut className="h-4 w-4" />
                    Keluar
                </Link>
            </div>
        </GuestLayout>
    );
}
