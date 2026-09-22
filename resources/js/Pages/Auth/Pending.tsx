import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import { Clock, LogOut, CheckCircle2, ShieldAlert } from 'lucide-react';
import { PageProps } from '@/types';

export default function Pending() {
    const { auth } = usePage<PageProps>().props;
    const isEmailVerified = Boolean(auth.user?.email_verified_at);

    return (
        <GuestLayout>
            <Head title="Menunggu Aktivasi Akun - SIMON BMN Gakkum" />

            <div className="text-center py-2">
                <div className="mx-auto h-14 w-14 bg-accent-50 border border-accent-200/80 rounded-2xl flex items-center justify-center mb-4 shadow-xs">
                    <Clock className="h-7 w-7 text-accent" />
                </div>

                <h2 className="text-xl font-bold text-slate-900 mb-1 tracking-tight">Akun Menunggu Aktivasi</h2>
                <p className="text-xs text-slate-500 mb-5">Balai Penegakan Hukum LH Wilayah Sumatera</p>

                {/* Progress Status Steps */}
                <div className="bg-slate-50 border border-slate-200/90 rounded-xl p-4 mb-5 text-left text-xs space-y-3">
                    <div className="flex items-start gap-3">
                        <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                        <div className="flex-1">
                            <span className="font-semibold text-slate-800 block">Pendaftaran Formulir Pegawai</span>
                            <span className="text-slate-500 text-[11px]">Data identitas dan unit kerja telah tersimpan.</span>
                        </div>
                    </div>

                    <div className="flex items-start gap-3">
                        {isEmailVerified ? (
                            <>
                                <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-semibold text-slate-800">Verifikasi Alamat Email</span>
                                        <span className="simon-badge-success !py-0 !px-1.5 !text-[10px]">Terverifikasi</span>
                                    </div>
                                    <span className="text-slate-500 text-[11px]">{auth.user?.email}</span>
                                </div>
                            </>
                        ) : (
                            <>
                                <ShieldAlert className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                                <div className="flex-1">
                                    <div className="flex items-center gap-2">
                                        <span className="font-semibold text-slate-800">Verifikasi Alamat Email</span>
                                        <span className="simon-badge-warning !py-0 !px-1.5 !text-[10px]">Belum Verifikasi</span>
                                    </div>
                                    <Link href={route('verification.notice')} className="text-primary hover:underline text-[11px] block mt-0.5 font-medium">
                                        Buka halaman verifikasi email &rarr;
                                    </Link>
                                </div>
                            </>
                        )}
                    </div>

                    <div className="flex items-start gap-3">
                        <div className="w-4 h-4 rounded-full border-2 border-accent flex items-center justify-center shrink-0 mt-0.5">
                            <div className="w-1.5 h-1.5 rounded-full bg-accent animate-pulse"></div>
                        </div>
                        <div className="flex-1">
                            <div className="flex items-center gap-2">
                                <span className="font-semibold text-slate-800">Aktivasi oleh Koordinator BMN</span>
                                <span className="simon-badge-accent !py-0 !px-1.5 !text-[10px]">Sedang Ditinjau</span>
                            </div>
                            <span className="text-slate-500 text-[11px]">
                                Petugas Tata Usaha / Koordinator BMN akan memvalidasi SK penugasan Anda untuk mengaktifkan hak akses sistem.
                            </span>
                        </div>
                    </div>
                </div>

                <p className="text-xs text-slate-500 leading-relaxed mb-6 px-2">
                    {isEmailVerified
                        ? 'Email Anda telah berhasil diverifikasi. Mohon menunggu atau hubungi Koordinator BMN / Pengelola SIMON untuk mengaktifkan akun Anda.'
                        : 'Silakan periksa kotak masuk atau folder spam email Anda dan klik tautan verifikasi.'}
                </p>

                <div className="flex flex-col sm:flex-row items-center justify-center gap-2.5">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="simon-button-secondary w-full sm:w-auto text-xs"
                    >
                        <LogOut className="h-3.5 w-3.5 mr-1.5 text-slate-500" />
                        Keluar dari Akun
                    </Link>
                </div>
            </div>
        </GuestLayout>
    );
}
