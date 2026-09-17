import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { DialogTitle } from '@headlessui/react';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import Modal from '@/Components/Modal';

type AssetOption = { id: string; name: string; nup: string | null; brand_type: string | null };
type Availability = { available: boolean; reasons: string[]; reservations: { start_date: string; end_date: string }[] };
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
    const [availability, setAvailability] = useState<Record<string, Availability>>({});
    const [checking, setChecking] = useState(false);
    const [checkError, setCheckError] = useState('');
    const [retry, setRetry] = useState(0);
    const selectedKey = form.data.asset_ids.join(',');
    const filtered = useMemo(() => availableAssets.filter(asset => (asset.name + ' ' + (asset.nup || '')).toLowerCase().includes(search.toLowerCase())), [availableAssets, search]);
    const selected = availableAssets.filter(asset => form.data.asset_ids.includes(asset.id));

    useEffect(() => {
        setAvailability({});
        setCheckError('');
        if (!selectedKey || !form.data.start_date || !form.data.end_date) {
            setChecking(false);
            return;
        }
        const controller = new AbortController();
        setChecking(true);
        const timer = window.setTimeout(async () => {
            const query = new URLSearchParams({ start_date: form.data.start_date, end_date: form.data.end_date });
            selectedKey.split(',').forEach(id => query.append('asset_ids[]', id));
            try {
                const response = await fetch(route('loans.availability') + '?' + query.toString(), { signal: controller.signal, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                const result = await response.json();
                if (!response.ok) throw new Error(result.message || 'Pemeriksaan jadwal gagal.');
                if (!controller.signal.aborted) setAvailability(result.assets);
            } catch (error) {
                if (!controller.signal.aborted) setCheckError(error instanceof Error ? error.message : 'Jadwal belum dapat diperiksa.');
            } finally {
                if (!controller.signal.aborted) setChecking(false);
            }
        }, 350);
        return () => { window.clearTimeout(timer); controller.abort(); };
    }, [selectedKey, form.data.start_date, form.data.end_date, retry]);

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
    const hasConflict = Object.values(availability).some(result => !result.available);
    return <AuthenticatedLayout header={<h1 className="text-2xl font-bold">{initialLoan ? 'Perbaiki draf peminjaman' : 'Ajukan peminjaman BMN'}</h1>}>
        <Head title="Pengajuan peminjaman" />
        <div className="mx-auto max-w-4xl">
            <Link className="text-sm font-semibold text-[#015850]" href={route('loans.index')}>← Daftar peminjaman</Link>
            <ol className="my-6 grid grid-cols-3 gap-2" aria-label="Tahap pengajuan">{['Tujuan & tanggal', 'Pilih barang', 'Periksa ringkasan'].map((label, index) => <li key={label} aria-current={step === index + 1 ? 'step' : undefined} className={'rounded-xl border p-3 text-sm ' + (step === index + 1 ? 'border-[#015850] bg-[#015850]/5 font-bold text-[#015850]' : 'border-slate-200 text-slate-500')}>{index + 1}. {label}</li>)}</ol>
            <form onSubmit={submit}>
                {step === 1 && <section className="simon-card space-y-5">
                    <h2 className="text-lg font-bold">Untuk keperluan apa barang digunakan?</h2>
                    <label className="block text-sm font-medium" htmlFor="purpose">Tujuan / keperluan<textarea id="purpose" className="simon-input mt-2" rows={4} required maxLength={5000} value={form.data.purpose} onChange={e => form.setData('purpose', e.target.value)} placeholder="Jelaskan kegiatan dan kebutuhan barang." /></label>
                    <div className="grid gap-4 sm:grid-cols-2"><label className="text-sm font-medium" htmlFor="start_date">Tanggal mulai<input id="start_date" type="date" required className="simon-input mt-2" value={form.data.start_date} onChange={e => form.setData('start_date', e.target.value)} /></label><label className="text-sm font-medium" htmlFor="end_date">Tanggal selesai<input id="end_date" type="date" required min={form.data.start_date || undefined} className="simon-input mt-2" value={form.data.end_date} onChange={e => form.setData('end_date', e.target.value)} /></label></div>
                    <p className="text-sm text-slate-500">Jadwal saat ini dihitung per hari. Pilihan barang akan diperiksa sesuai tanggal yang Anda isi.</p>
                </section>}
                {step === 2 && <section className="simon-card">
                    <h2 className="text-lg font-bold">Pilih maksimal 30 barang</h2>
                    <label className="mt-4 block text-sm" htmlFor="asset-search">Cari nama barang atau NUP<input id="asset-search" className="simon-input mt-2" value={search} onChange={e => setSearch(e.target.value)} placeholder="Ketik nama atau NUP…" /></label>
                    <div className="mt-4 max-h-96 space-y-2 overflow-y-auto">{filtered.map(asset => {
                        const checked = form.data.asset_ids.includes(asset.id);
                        return <label key={asset.id} className={'flex cursor-pointer gap-3 rounded-xl border p-3 ' + (checked ? 'border-[#015850] bg-[#015850]/5' : 'border-slate-200')}><input type="checkbox" className="mt-1 rounded" checked={checked} disabled={!checked && form.data.asset_ids.length >= 30} onChange={e => form.setData('asset_ids', e.target.checked ? [...form.data.asset_ids, asset.id] : form.data.asset_ids.filter(id => id !== asset.id))} /><span><span className="block text-sm font-semibold">{asset.name}</span><span className="text-xs text-slate-500">NUP {asset.nup || '—'} · {asset.brand_type || 'Tanpa merek'}</span></span></label>;
                    })}{!filtered.length && <p className="py-6 text-center text-sm text-slate-500">Tidak ada barang sesuai pencarian atau cakupan Anda.</p>}</div>
                    <p className="mt-3 text-sm font-semibold">{form.data.asset_ids.length} barang dipilih</p>
                </section>}
                {step === 3 && <section className="simon-card"><h2 className="text-lg font-bold">Periksa pengajuan Anda</h2><p className="mt-3 whitespace-pre-wrap">{form.data.purpose}</p><p className="mt-2 text-sm">{form.data.start_date} sampai {form.data.end_date}</p><ul className="mt-4 space-y-2">{selected.map(asset => <li className="rounded-lg bg-slate-50 p-3 text-sm" key={asset.id}>{asset.name} · NUP {asset.nup || '—'}</li>)}</ul></section>}
                {step > 1 && selected.length > 0 && <section className="simon-card mt-4" aria-live="polite">
                    <h2 className="font-bold">Pratinjau ketersediaan</h2>
                    <p className="mt-2 text-xs text-slate-500">Ini bukan reservasi. Koordinator memeriksa ulang sebelum persetujuan. Identitas peminjam lain tidak ditampilkan.</p>
                    {checking && <p className="mt-3 animate-pulse text-sm">Memeriksa jadwal…</p>}
                    {checkError && <div className="mt-3 text-sm text-amber-800"><p>{checkError}</p><button type="button" className="mt-2 underline" onClick={() => setRetry(value => value + 1)}>Coba periksa ulang</button><p className="mt-2">Anda dapat menyimpan draf atau mengajukan untuk diperiksa Koordinator.</p></div>}
                    {!checking && selected.map(asset => availability[asset.id] && <div key={asset.id} className="mt-3 border-t border-slate-100 pt-3 text-sm"><p className="font-semibold">{asset.name}: {availability[asset.id].available ? 'Tidak ditemukan bentrok' : 'Perlu penyesuaian'}</p>{availability[asset.id].reasons.map(reason => <p className="mt-1 text-amber-800" key={reason}>{reason}</p>)}{availability[asset.id].reservations.map((reservation, index) => <p className="mt-1 text-xs text-slate-500" key={index}>Jadwal terisi: {reservation.start_date.slice(0, 10)} — {reservation.end_date.slice(0, 10)}</p>)}</div>)}
                </section>}
                {Object.keys(form.errors).length > 0 && <div role="alert" className="mt-4 rounded-xl bg-red-50 p-4 text-sm text-red-800">{Object.entries(form.errors).map(([key, error]) => <p key={key}>{error}</p>)}</div>}
                <div className="mt-6 flex flex-wrap justify-between gap-3">
                    <div>{step > 1 && <button type="button" className="simon-button-secondary" disabled={form.processing} onClick={() => setStep(step - 1)}>← Sebelumnya</button>}</div>
                    <div className="flex flex-wrap gap-3">{step > 1 && <button type="button" className="simon-button-secondary" disabled={form.processing || !selected.length} onClick={() => send(true)}>Simpan draf</button>}<button className="simon-button" type="submit" disabled={form.processing || (step === 2 && !selected.length)}>{step === 3 ? 'Ajukan peminjaman' : 'Lanjut →'}</button></div>
                </div>
            </form>
            <Modal show={confirm} onClose={() => !form.processing && setConfirm(false)} closeable={!form.processing} maxWidth="lg">
                <div className="space-y-4 p-6"><DialogTitle className="text-xl font-bold">Kirim pengajuan peminjaman?</DialogTitle><p className="text-sm">{selected.length} barang, periode {form.data.start_date} sampai {form.data.end_date}. Pengajuan akan menunggu persetujuan Koordinator.</p>{hasConflict && <p className="rounded-lg bg-amber-50 p-3 text-sm text-amber-900">Ada kendala ketersediaan. Pengajuan bukan persetujuan; Koordinator perlu memeriksa dan jadwal mungkin perlu direvisi.</p>}<div className="flex justify-end gap-3"><button type="button" className="simon-button-secondary" disabled={form.processing} onClick={() => setConfirm(false)}>Periksa lagi</button><button type="button" className="simon-button" disabled={form.processing} onClick={() => send(false)}>{form.processing ? 'Mengirim…' : 'Ya, kirim pengajuan'}</button></div></div>
            </Modal>
        </div>
    </AuthenticatedLayout>;
}
