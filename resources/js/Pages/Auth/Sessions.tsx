import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ActionForm from '@/Components/ActionForm';
import { Head, Link } from '@inertiajs/react';

type Session = { current: boolean; ip: string; device: string; last_active: number };
export default function Sessions({ sessions, supported }: { sessions: Session[]; supported: boolean }) {
    return <AuthenticatedLayout header={<h1 className="text-2xl font-bold">Perangkat & sesi masuk</h1>}><Head title="Perangkat & sesi" />
        <p className="mb-6 text-sm text-slate-600">Kenali perangkatmu. Jika ada aktivitas yang tidak dikenal, keluarkan perangkat lain lalu ganti kata sandi.</p>
        <div className="space-y-3">{sessions.map((session, index) => <section key={index} className="simon-card"><h2 className="font-semibold">{session.current ? 'Perangkat ini' : 'Perangkat lain'}</h2><p className="mt-2 break-words text-sm">{session.device}</p><p className="mt-2 text-sm text-slate-500">IP {session.ip || 'Tidak tersedia'} · Terakhir aktif {new Date(session.last_active * 1000).toLocaleString('id-ID')}</p></section>)}</div>
        {!supported && <p className="simon-card">Daftar perangkat memerlukan penyimpanan sesi database.</p>}
        <div className="mt-6 flex flex-wrap gap-3"><ActionForm title="Keluar dari perangkat lain" url={route('sessions.revoke')} fields={[{ name: 'current_password', label: 'Kata sandi saat ini', type: 'password' }]} /><Link href={route('profile.edit')} className="simon-button-secondary">Ganti kata sandi</Link></div>
    </AuthenticatedLayout>;
}
