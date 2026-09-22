import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        window.dispatchEvent(
            new CustomEvent('global-load-start', {
                detail: { message: 'Mengirim tautan verifikasi...' },
            })
        );

        post(route('verification.send'), {
            onFinish: () => {
                window.dispatchEvent(new CustomEvent('global-load-stop'));
            },
            onError: () => {
                window.dispatchEvent(new CustomEvent('global-load-stop'));
            },
        });
    };

    return (
        <GuestLayout>
            <Head title="Verifikasi Email - SIMON BMN Gakkum" />

            <div className="text-center mb-6">
                <div className="w-14 h-14 bg-primary-50 text-primary border border-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg className="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.8" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <h1 className="text-xl font-bold text-slate-900 tracking-tight">Verifikasi Alamat Email</h1>
                <p className="text-xs text-slate-500 mt-1">Langkah 2 dari 3: Validasi Kepemilikan Akun</p>
            </div>

            <div className="mb-6 text-sm text-slate-600 leading-relaxed bg-slate-50 border border-slate-200 rounded-xl p-4">
                Buka tautan verifikasi yang kami kirim ke emailmu. Setelah email terverifikasi,
                akun baru tetap perlu diaktifkan oleh pengelola. Periksa folder spam
                atau kirim ulang tautan jika belum diterima.
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 flex items-start gap-3">
                    <svg className="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div className="text-xs text-emerald-800 leading-relaxed">
                        <strong className="font-semibold block">Tautan Terkirim!</strong>
                        Tautan verifikasi baru telah berhasil dikirim ke alamat email pendaftaran Anda.
                    </div>
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full flex items-center justify-center gap-2 py-2.5 px-4 bg-primary hover:bg-primary-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all duration-150 active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>{processing ? 'Mengirim...' : 'Kirim ulang verifikasi'}</span>
                </button>

                <div className="pt-2 text-center">
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="text-xs text-slate-500 hover:text-slate-800 font-medium underline underline-offset-4 decoration-slate-300 hover:decoration-slate-600 transition-colors"
                    >
                        Keluar dari Akun
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
