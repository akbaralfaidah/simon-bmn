import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { DialogTitle } from '@headlessui/react';
import { FormEvent, useMemo, useState } from 'react';
import Modal from '@/Components/Modal';
import { ArrowLeft, Calendar, Boxes, CheckCircle2 } from 'lucide-react';

type AssetOption = { id: string; name: string; nup: string | null; brand_type: string | null };
type LoanForm = { submission_key: string; purpose: string; start_date: string; end_date: string; asset_ids: string[]; version: number };

export default function Create({ availableAssets, submissionKey, initialLoan }: { availableAssets: AssetOption[]; submissionKey?: string; initialLoan?: LoanForm & { id: number } }) {
    const form = useForm<LoanForm>({
        submission_key: submissionKey || '',
        purpose: initialLoan?.purpose || '',
        start_date: initialLoan?.start_date || '',
        end_date: initialLoan?.end_date || '',
        asset_ids: initialLoan?.asset_ids || [],
        version: initialLoan?.version || 1,
    });
    const [step, setStep] = useState(1);
    const [search, setSearch] = useState('');
    const [confirm, setConfirm] = useState(false);

    const stepItems = [
        { step: 1, label: 'Tujuan & tanggal', icon: Calendar },
        { step: 2, label: 'Pilih barang', icon: Boxes },
        { step: 3, label: 'Periksa ringkasan', icon: CheckCircle2 },
    ];

    const filtered = useMemo(() => availableAssets.filter(asset => (asset.name + ' ' + (asset.nup || '')).toLowerCase().includes(search.toLowerCase())), [availableAssets, search]);
    const selected = availableAssets.filter(asset => form.data.asset_ids.includes(asset.id));

    function send(draft: boolean) {
        form.transform(data => ({ ...data, draft }));
        form.post(initialLoan ? route('loans.update', initialLoan.id) : route('loans.store'), {
            onError: errors => { setConfirm(false); setStep(errors.purpose || errors.start_date || errors.end_date ? 1 : 2); },
        });
    }

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (step < 3) {
            if (step === 1 && form.data.end_date < form.data.start_date) {
                form.setError('end_date', 'Tanggal selesai harus sama atau setelah tanggal mulai.');
                return;
            }
            if (step === 2 && !form.data.asset_ids.length) return;
            setStep(step + 1);
        } else {
            setConfirm(true);
        }
    }

    return (
        <AuthenticatedLayout header={<h1 className="text-2xl font-bold">{initialLoan ? 'Perbaiki draf peminjaman' : 'Ajukan peminjaman BMN'}</h1>}>
            <Head title="Pengajuan peminjaman" />
            <div className="mx-auto max-w-4xl">
                <Link className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#015850] hover:underline" href={route('loans.index')}>
                    <ArrowLeft className="h-4 w-4" />
                    <span>Daftar peminjaman</span>
                </Link>
                <ol className="my-6 grid grid-cols-3 gap-2 sm:gap-3" aria-label="Tahap pengajuan">
                    {stepItems.map((item) => {
                        const ItemIcon = item.icon;
                        const isCurrent = step === item.step;
                        const isCompleted = step > item.step;
                        return (
                            <li
                                key={item.step}
                                aria-current={isCurrent ? 'step' : undefined}
                                className={`flex items-center gap-2 rounded-xl border p-3 text-xs sm:text-sm transition-all ${
                                    isCurrent
                                        ? 'border-[#015850] bg-[#015850]/5 font-bold text-[#015850] ring-1 ring-[#015850]/20'
                                        : isCompleted
                                            ? 'border-emerald-200 bg-emerald-50/60 font-medium text-emerald-800'
                                            : 'border-slate-200 bg-white text-slate-500'
                                }`}
                            >
                                <div className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-md ${
                                    isCurrent ? 'bg-[#015850] text-white' : isCompleted ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-400'
                                }`}>
                                    <ItemIcon className="h-3.5 w-3.5" />
                                </div>
                                <span className="truncate">{item.step}. {item.label}</span>
                            </li>
                        );
                    })}
                </ol>
                <form onSubmit={submit}>
                    {step === 1 && (
                        <section className="simon-card space-y-5">
                            <h2 className="text-lg font-bold">Untuk keperluan apa barang digunakan?</h2>
                            <label className="block text-sm font-medium" htmlFor="purpose">
                                Tujuan / keperluan
                                <textarea id="purpose" className="simon-input mt-2" rows={4} required maxLength={5000} value={form.data.purpose} onChange={e => form.setData('purpose', e.target.value)} placeholder="Jelaskan kegiatan dan kebutuhan barang." />
                            </label>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <label className="text-sm font-medium" htmlFor="start_date">
                                    Tanggal mulai
                                    <input id="start_date" type="date" required className="simon-input mt-2" value={form.data.start_date} onChange={e => form.setData('start_date', e.target.value)} />
                                </label>
                                <label className="text-sm font-medium" htmlFor="end_date">
                                    Tanggal selesai
                                    <input id="end_date" type="date" required min={form.data.start_date || undefined} className="simon-input mt-2" value={form.data.end_date} onChange={e => form.setData('end_date', e.target.value)} />
                                </label>
                            </div>
                            <p className="text-sm text-slate-500">Jadwal saat ini dihitung per hari.</p>
                        </section>
                    )}
                    {step === 2 && (
                        <section className="simon-card">
                            <h2 className="text-lg font-bold">Pilih maksimal 30 barang</h2>
                            <label className="mt-4 block text-sm" htmlFor="asset-search">
                                Cari nama barang atau NUP
                                <input id="asset-search" className="simon-input mt-2" value={search} onChange={e => setSearch(e.target.value)} placeholder="Ketik nama atau NUP…" />
                            </label>
                            <div className="mt-4 max-h-96 space-y-2 overflow-y-auto">
                                {filtered.map(asset => {
                                    const checked = form.data.asset_ids.includes(asset.id);
                                    return (
                                        <label key={asset.id} className={'flex cursor-pointer gap-3 rounded-xl border p-3 ' + (checked ? 'border-[#015850] bg-[#015850]/5' : 'border-slate-200')}>
                                            <input type="checkbox" className="mt-1 rounded" checked={checked} disabled={!checked && form.data.asset_ids.length >= 30} onChange={e => form.setData('asset_ids', e.target.checked ? [...form.data.asset_ids, asset.id] : form.data.asset_ids.filter(id => id !== asset.id))} />
                                            <span>
                                                <span className="block text-sm font-semibold">{asset.name}</span>
                                                <span className="text-xs text-slate-500">NUP {asset.nup || '—'} · {asset.brand_type || 'Tanpa merek'}</span>
                                            </span>
                                        </label>
                                    );
                                })}
                                {!filtered.length && <p className="py-6 text-center text-sm text-slate-500">Tidak ada barang sesuai pencarian atau cakupan Anda.</p>}
                            </div>
                            <p className="mt-3 text-sm font-semibold">{form.data.asset_ids.length} barang dipilih</p>
                        </section>
                    )}
                    {step === 3 && (
                        <section className="simon-card">
                            <h2 className="text-lg font-bold">Periksa pengajuan Anda</h2>
                            <p className="mt-3 whitespace-pre-wrap">{form.data.purpose}</p>
                            <p className="mt-2 text-sm">{form.data.start_date} sampai {form.data.end_date}</p>
                            <ul className="mt-4 space-y-2">
                                {selected.map(asset => (
                                    <li className="rounded-lg bg-slate-50 p-3 text-sm" key={asset.id}>
                                        {asset.name} · NUP {asset.nup || '—'}
                                    </li>
                                ))}
                            </ul>
                        </section>
                    )}
                    {Object.keys(form.errors).length > 0 && (
                        <div role="alert" className="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">
                            {Object.entries(form.errors).map(([key, error]) => <p key={key}>{error}</p>)}
                        </div>
                    )}
                    <div className="mt-6 flex flex-wrap justify-between gap-3">
                        <div>
                            {step > 1 && (
                                <button type="button" className="simon-button-secondary" disabled={form.processing} onClick={() => setStep(step - 1)}>
                                    ← Sebelumnya
                                </button>
                            )}
                        </div>
                        <div className="flex flex-wrap gap-3">
                            {step > 1 && (
                                <button type="button" className="simon-button-secondary" disabled={form.processing || !selected.length} onClick={() => send(true)}>
                                    Simpan draf
                                </button>
                            )}
                            <button className="simon-button" type="submit" disabled={form.processing || (step === 2 && !selected.length)}>
                                {step === 3 ? 'Ajukan peminjaman' : 'Lanjut →'}
                            </button>
                        </div>
                    </div>
                </form>
                <Modal show={confirm} onClose={() => !form.processing && setConfirm(false)} closeable={!form.processing} maxWidth="lg">
                    <div className="space-y-4 p-6">
                        <DialogTitle className="text-xl font-bold">Kirim pengajuan peminjaman?</DialogTitle>
                        <p className="text-sm">{selected.length} barang, periode {form.data.start_date} sampai {form.data.end_date}. Pengajuan akan menunggu persetujuan Koordinator.</p>
                        <div className="flex justify-end gap-3">
                            <button type="button" className="simon-button-secondary" disabled={form.processing} onClick={() => setConfirm(false)}>
                                Periksa lagi
                            </button>
                            <button type="button" className="simon-button" disabled={form.processing} onClick={() => send(false)}>
                                {form.processing ? 'Mengirim…' : 'Ya, kirim pengajuan'}
                            </button>
                        </div>
                    </div>
                </Modal>
            </div>
        </AuthenticatedLayout>
    );
}
