import DangerButton from '@/Components/DangerButton';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import Modal from '@/Components/Modal';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import { Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect, useRef } from 'react';
import { AlertTriangle, FileSignature } from 'lucide-react';

interface Props {
    bast: any;
    role: 'peminjam' | 'pj' | 'koordinator' | null;
    roleLabel: string;
    isOpen: boolean;
    onClose: () => void;
    hasSignature: boolean;
}

export default function SignBastModal({
    bast,
    role,
    roleLabel,
    isOpen,
    onClose,
    hasSignature,
}: Props) {
    const passwordInput = useRef<HTMLInputElement>(null);

    const {
        data,
        setData,
        post,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        password: '',
        role: role || '',
    });

    useEffect(() => {
        if (role) {
            setData('role', role);
        }
    }, [role]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!bast || !role) return;

        post(route('basts.sign', bast.id), {
            preserveScroll: true,
            onSuccess: () => {
                handleClose();
            },
            onError: () => {
                passwordInput.current?.focus();
            },
        });
    };

    const handleClose = () => {
        reset();
        clearErrors();
        onClose();
    };

    if (!isOpen || !bast || !role) return null;

    return (
        <Modal show={isOpen} onClose={handleClose} maxWidth="md">
            {!hasSignature ? (
                <div className="p-6">
                    <div className="flex items-center gap-3 text-amber-600 mb-3">
                        <AlertTriangle className="h-6 w-6 shrink-0" />
                        <h3 className="text-lg font-bold text-slate-900">
                            Tanda Tangan Belum Tersedia
                        </h3>
                    </div>

                    <p className="text-sm text-slate-600 leading-relaxed">
                        Anda belum memiliki tanda tangan digital yang tersimpan di akun Anda. Untuk menandatangani dokumen BAST ini secara langsung, silakan unggah tanda tangan Anda melalui menu Pengaturan Profil akun terlebih dahulu.
                    </p>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton onClick={handleClose}>
                            Batal
                        </SecondaryButton>
                        <Link
                            href={route('profile.edit')}
                            className="inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white shadow-xs hover:bg-primary-700 active:scale-[0.98] transition-all"
                        >
                            Buka Pengaturan Akun
                        </Link>
                    </div>
                </div>
            ) : (
                <form onSubmit={submit} className="p-6">
                    <div className="flex items-center gap-3 text-primary mb-2">
                        <span className="flex h-8 w-8 items-center justify-center rounded-full bg-primary-50 text-primary">
                            <FileSignature className="h-4 w-4" />
                        </span>
                        <div>
                            <h3 className="text-lg font-bold text-slate-900">
                                Tandatangani BAST Digital
                            </h3>
                            <p className="text-xs text-slate-500 font-mono">
                                {bast.bast_number}
                            </p>
                        </div>
                    </div>

                    <div className="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600 space-y-1">
                        <p>
                            Peran Penandatangan:{' '}
                            <span className="font-semibold text-slate-900">
                                {roleLabel}
                            </span>
                        </p>
                        <p>
                            Tanda tangan tersimpan Anda akan dibubuhkan pada dokumen BAST ini. Tindakan ini tercatat resmi dalam log audit sistem.
                        </p>
                    </div>

                    <div className="mt-4">
                        <InputLabel
                            htmlFor="bast-sign-password"
                            value="Masukkan Kata Sandi Akun Anda"
                        />
                        <TextInput
                            id="bast-sign-password"
                            ref={passwordInput}
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="Kata sandi akun Anda"
                            required
                            autoFocus
                        />
                        <InputError message={errors.password} className="mt-1.5" />
                    </div>

                    <div className="mt-6 flex justify-end gap-3">
                        <SecondaryButton type="button" onClick={handleClose}>
                            Batal
                        </SecondaryButton>
                        <PrimaryButton type="submit" disabled={processing || !data.password}>
                            {processing ? 'Menandatangani...' : 'Konfirmasi & Tandatangani'}
                        </PrimaryButton>
                    </div>
                </form>
            )}
        </Modal>
    );
}
