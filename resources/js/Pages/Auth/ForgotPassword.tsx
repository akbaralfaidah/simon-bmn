import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <GuestLayout>
            <Head title="Lupa kata sandi" />
            <h1 className="mb-2 text-xl font-bold">Lupa kata sandi?</h1>

            <div className="mb-4 text-sm text-gray-600">
                Masukkan email akunmu. Jika terdaftar, tautan untuk membuat kata
                sandi baru akan dikirimkan melalui email.
            </div>

            {status && (
                <div className="mb-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form onSubmit={submit}>
                <InputLabel htmlFor="email" value="Email akun" />
                <TextInput
                    id="email"
                    type="email"
                    autoComplete="email"
                    required
                    name="email"
                    value={data.email}
                    className="mt-1 block w-full"
                    isFocused={true}
                    onChange={(e) => setData('email', e.target.value)}
                />

                <InputError message={errors.email} className="mt-2" />

                <div className="mt-4 flex items-center justify-end">
                    <PrimaryButton className="ms-4" disabled={processing}>
                        {processing ? 'Mengirim…' : 'Kirim tautan pemulihan'}
                    </PrimaryButton>
                </div>
            </form>
            <Link href={route('login')} className="mt-6 inline-block text-sm text-brand-primary underline">Kembali ke masuk</Link>
        </GuestLayout>
    );
}
