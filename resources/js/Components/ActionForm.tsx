import { DialogTitle } from '@headlessui/react';
import { useForm } from '@inertiajs/react';
import { FormEvent, useId, useState } from 'react';
import Modal from './Modal';
import TextInput from './TextInput';
import { Loader2 } from 'lucide-react';

export type Field = { name: string; label: string; type?: string; required?: boolean; options?: { value: string | number; label: string }[]; hint?: string };
export default function ActionForm({ title, url, fields = [], initial = {}, label, description, buttonClassName, buttonContent, children, method = 'post' }: { title: string; url: string; fields?: Field[]; initial?: Record<string, any>; label?: string; description?: string; buttonClassName?: string; buttonContent?: React.ReactNode; children?: React.ReactNode; method?: 'post' | 'delete' | 'put' }) {
    const [open, setOpen] = useState(false);
    const formId = useId();
    const form = useForm<Record<string, any>>({ ...Object.fromEntries(fields.map(f => [f.name, ['multiselect', 'images'].includes(f.type || '') ? [] : f.type === 'file' ? null : ''])), ...initial });
    function submit(event: FormEvent) {
        event.preventDefault();
        const submitFn = method === 'delete' ? form.delete : method === 'put' ? form.put : form.post;

        window.dispatchEvent(new CustomEvent('global-load-start', {
            detail: { message: `Memproses ${title}...` }
        }));

        submitFn(url, {
            preserveScroll: true,
            onSuccess: () => {
                setOpen(false);
                form.reset();
            },
            onFinish: () => {
                window.dispatchEvent(new CustomEvent('global-load-stop'));
            },
        });
    }
    return <>
        <button type="button" className={buttonClassName || "simon-button"} onClick={() => { form.clearErrors(); form.setData({ ...Object.fromEntries(fields.map(field => [field.name, ['multiselect', 'images'].includes(field.type || '') ? [] : field.type === 'file' ? null : ''])), ...initial }); setOpen(true); }}>{buttonContent || children || label || title}</button>
        <Modal show={open} onClose={() => !form.processing && setOpen(false)} maxWidth="lg" closeable={!form.processing}>
            <form onSubmit={submit} className="space-y-5 p-6">
                <DialogTitle className="text-xl font-bold text-[#1E1935]">{title}</DialogTitle>
                <p className="text-sm text-slate-600">{description || 'Periksa data sebelum melanjutkan. Tindakan ini akan dicatat dalam riwayat sistem.'}</p>
                {fields.map(field => {
                    if (field.type === 'hidden') {
                        return (
                            <input
                                key={field.name}
                                type="hidden"
                                id={formId + '-' + field.name}
                                name={field.name}
                                value={form.data[field.name]}
                            />
                        );
                    }
                    return (
                        <div key={field.name}>
                            <label htmlFor={formId + '-' + field.name} className="mb-1 block text-sm font-medium">{field.label}{field.required !== false && ' *'}</label>
                            {field.type === 'multiselect' ? <fieldset className="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3" aria-label={field.label}>
                                {!field.options?.length && <p className="text-sm text-slate-500">Belum ada barang yang tersedia.</p>}
                                {field.options?.map(option => <label key={option.value} className="flex cursor-pointer items-start gap-3 rounded-lg p-2 hover:bg-slate-50">
                                    <input type="checkbox" className="mt-0.5 rounded border-slate-300 text-brand-primary focus:ring-brand-primary" checked={(form.data[field.name] as string[]).includes(String(option.value))} onChange={e => {
                                        const selected = form.data[field.name] as string[];
                                        form.setData(field.name, e.target.checked ? [...selected, String(option.value)] : selected.filter(value => value !== String(option.value)));
                                    }} />
                                    <span className="text-sm">{option.label}</span>
                                </label>)}
                            </fieldset> : field.type === 'select' ? <select id={formId + '-' + field.name} className="simon-input" required={field.required !== false} value={form.data[field.name]} onChange={e => form.setData(field.name, e.target.value)}>
                                <option value="">Pilih…</option>
                                {field.options?.map(option => <option value={option.value} key={option.value}>{option.label}</option>)}
                            </select> : field.type === 'textarea' ? <textarea id={formId + '-' + field.name} className="simon-input" rows={3} required={field.required !== false} value={form.data[field.name]} onChange={e => form.setData(field.name, e.target.value)} /> : field.type === 'file' ? <input id={formId + '-' + field.name} className="simon-input" type="file" accept=".pdf" required={field.required !== false} onChange={e => form.setData(field.name, e.target.files?.[0] || null)} /> : field.type === 'images' ? <input id={formId + '-' + field.name} className="simon-input" type="file" accept="image/jpeg,image/png,image/webp" multiple required={field.required !== false} onChange={e => form.setData(field.name, Array.from(e.target.files || []))} /> : <TextInput id={formId + '-' + field.name} className="simon-input" type={field.type || 'text'} required={field.required !== false} value={form.data[field.name]} onChange={e => form.setData(field.name, e.target.value)} />}
                            {field.hint && <p className="mt-1 text-xs text-slate-500">{field.hint}</p>}
                        </div>
                    );
                })}
                {Object.keys(form.errors).length > 0 && <div role="alert" className="rounded-xl bg-red-50 p-3 text-sm text-red-800">{Object.entries(form.errors).map(([key, error]) => <p key={key}>{String(error)}</p>)}</div>}
                {form.progress && <progress aria-label="Progres unggahan" className="w-full" value={form.progress.percentage} max={100} />}
                <div className="flex justify-end gap-3 pt-2">
                    <button
                        className="simon-button-secondary"
                        type="button"
                        disabled={form.processing}
                        onClick={() => setOpen(false)}
                    >
                        Batal
                    </button>
                    <button
                        className="simon-button inline-flex items-center justify-center gap-2 min-w-[110px]"
                        disabled={form.processing}
                    >
                        {form.processing ? (
                            <>
                                <Loader2 className="animate-spin h-3.5 w-3.5 text-white" />
                                <span>Memproses…</span>
                            </>
                        ) : (
                            'Konfirmasi'
                        )}
                    </button>
                </div>
            </form>
        </Modal>
    </>;
}
