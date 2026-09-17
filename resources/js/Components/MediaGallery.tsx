import { usePoll } from '@inertiajs/react';
import { useEffect } from 'react';
import ActionForm from './ActionForm';

type Photo = { id: number; processing_status: string; file_path: string; thumbnail_path: string; can_retry?: boolean; can_replace?: boolean; replacement_media_id?: number; replacement_reason?: string };
export default function MediaGallery({ photos, poll = true }: { photos: Photo[]; poll?: boolean }) {
    const pending = poll && photos.some(photo => ['queued', 'processing'].includes(photo.processing_status));
    const { start, stop } = usePoll(10000, { only: ['asset'] }, { autoStart: false });
    useEffect(() => { if (pending) start(); else stop(); return stop; }, [pending, start, stop]);
    return <div className="flex flex-wrap gap-3">{photos.map((photo, index) => <div key={photo.id} className="w-36 rounded-xl border border-slate-200 p-2">
        {photo.processing_status === 'ready' ? <a href={photo.file_path} target="_blank" rel="noopener noreferrer"><img src={photo.thumbnail_path} width={128} height={128} loading="lazy" alt={`Foto ${index + 1}; buka ukuran penuh`} className="aspect-square w-full rounded-lg object-contain" /></a> : <div className="flex aspect-square items-center justify-center p-2 text-center text-sm text-slate-500">{photo.processing_status === 'failed' ? 'Foto gagal diproses' : photo.processing_status === 'cancelled' ? 'Akses pemrosesan dicabut' : 'Menunggu pemeriksaan / optimasi'}</div>}
        {photo.can_retry && photo.processing_status === 'failed' && <ActionForm title="Coba proses lagi" url={route('media.retry', photo.id)} />}
        {photo.replacement_media_id && <p className="mt-2 text-xs text-slate-600">Diarsipkan; diganti foto #{photo.replacement_media_id}. {photo.replacement_reason}</p>}
        {photo.can_replace && <ActionForm title="Ganti bukti gagal" url={route('media.replace', photo.id)} description="Unggah satu foto pengganti dan jelaskan alasannya. Foto lama tetap tersimpan dalam riwayat. Bukti pengganti wajib lolos pemeriksaan sebelum proses dapat dilanjutkan." fields={[{ name: 'images', label: 'Satu foto pengganti', type: 'images', hint: 'JPG, PNG, atau WebP; maksimal 10 MB dan 24 megapiksel.' }, { name: 'reason', label: 'Alasan penggantian', type: 'textarea', hint: 'Minimal 10 karakter.' }]} />}
    </div>)}</div>;
}
