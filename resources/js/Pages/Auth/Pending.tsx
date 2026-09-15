import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link } from '@inertiajs/react';

export default function Pending() {
    return (
        <GuestLayout>
            <Head title="Menunggu Aktivasi" />

            <div className="mb-4 text-sm text-gray-600">
                Pendaftaran berhasil! Saat ini akun Anda berstatus Pegawai dan sedang menunggu verifikasi/aktivasi dari administrator.
            </div>

            <div className="mb-4 text-sm text-gray-600">
                Jika email Anda terdaftar secara resmi, petunjuk pemulihan atau aktivasi akan dikirimkan, atau Anda dapat menghubungi Koordinator untuk mengaktifkan akun Anda.
            </div>

            <div className="mt-4 flex items-center justify-between">
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
                >
                    Log Out
                </Link>
            </div>
        </GuestLayout>
    );
}
