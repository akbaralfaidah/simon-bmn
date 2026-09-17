import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import NotificationDialog from './NotificationDialog';
import { PageProps } from '@/types';

export default function FlashDialog() {
    const { flash } = usePage<PageProps<{ flash?: { success?: string; error?: string; status?: string } }>>().props;
    const [message, setMessage] = useState<{ text: string; error: boolean } | null>(null);
    useEffect(() => {
        const status = flash?.status === 'verification-link-sent' ? 'Tautan verifikasi baru telah dikirim. Periksa emailmu.' : flash?.status;
        const text = flash?.error || flash?.success || status;
        if (text) setMessage({ text, error: !!flash?.error });
    }, [flash]);
    return <NotificationDialog show={!!message} type={message?.error ? 'error' : 'success'} title={message?.error ? 'Perlu diperiksa' : 'Informasi'} description={message?.text || ''} primaryActionText="Mengerti" onPrimaryAction={() => setMessage(null)} onClose={() => setMessage(null)} />;
}
