import React from 'react';

export interface StatusBadgeProps {
    status: string;
    type?: 'general' | 'loan';
    className?: string;
}

export const LOAN_STAGE_CONFIGS: Record<string, { label: string; stage: number; color: string; dot: string }> = {
    draft: {
        label: 'Tahap 1: Draf Pengajuan',
        stage: 1,
        color: 'bg-slate-100 text-slate-700 border border-slate-200',
        dot: 'bg-slate-400',
    },
    revision_requested: {
        label: 'Tahap 1: Perlu Revisi',
        stage: 1,
        color: 'bg-amber-50 text-amber-800 border border-amber-200',
        dot: 'bg-amber-500',
    },
    pending: {
        label: 'Tahap 2: Menunggu Persetujuan',
        stage: 2,
        color: 'bg-blue-50 text-blue-700 border border-blue-200',
        dot: 'bg-blue-500',
    },
    pending_approval: {
        label: 'Tahap 2: Menunggu Persetujuan',
        stage: 2,
        color: 'bg-blue-50 text-blue-700 border border-blue-200',
        dot: 'bg-blue-500',
    },
    approved: {
        label: 'Tahap 3: BAST & Serah Fisik',
        stage: 3,
        color: 'bg-indigo-50 text-indigo-700 border border-indigo-200',
        dot: 'bg-indigo-500',
    },
    active: {
        label: 'Tahap 4: Penggunaan',
        stage: 4,
        color: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        dot: 'bg-emerald-500',
    },
    returning: {
        label: 'Tahap 5: Pengembalian',
        stage: 5,
        color: 'bg-amber-50 text-amber-800 border border-amber-200',
        dot: 'bg-amber-500',
    },
    completed: {
        label: 'Selesai',
        stage: 5,
        color: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        dot: 'bg-emerald-500',
    },
    rejected: {
        label: 'Ditolak',
        stage: 0,
        color: 'bg-rose-50 text-rose-700 border border-rose-200',
        dot: 'bg-rose-500',
    },
    cancelled: {
        label: 'Dibatalkan',
        stage: 0,
        color: 'bg-slate-100 text-slate-600 border border-slate-200',
        dot: 'bg-slate-400',
    },
};

const generalLabels: Record<string, string> = {
    draft: 'Draf',
    pending: 'Menunggu',
    pending_approval: 'Menunggu persetujuan',
    approved: 'Disetujui',
    prepared: 'Siap diserahkan',
    handed_over: 'Menunggu penerimaan',
    active: 'Aktif',
    returning: 'Proses pengembalian',
    return_requested: 'Menunggu pemeriksaan',
    needs_repair: 'Perlu perbaikan peminjam',
    inspected: 'Menunggu penutupan',
    returned: 'Dikembalikan',
    completed: 'Selesai',
    rejected: 'Ditolak',
    cancelled: 'Dibatalkan',
    proposed: 'Diusulkan',
    in_progress: 'Dalam perawatan',
    submitted: 'Diajukan',
    resolved: 'Ditindaklanjuti',
    in_transit: 'Dalam perjalanan',
    revoked: 'Ditutup',
    received: 'Sudah diperiksa',
    open: 'Terbuka',
    closed: 'Ditutup',
    uploaded: 'Menunggu verifikasi',
    verified: 'Terverifikasi',
    preview: 'Pratinjau',
    committed: 'Sudah diimpor',
    found: 'Ditemukan',
    missing: 'Tidak ditemukan',
    damaged: 'Rusak',
    error: 'Perlu perbaikan',
    validated: 'Valid',
};

const workflowLabels: Record<string, string> = {
    sub_review: 'Review Subkoordinator',
    coordinator_review: 'Review Koordinator',
    head_review: 'Pengesahan Kepala TU',
    authorized: 'Siap diarsipkan',
    wrong_location: 'Beda lokasi',
    wrong_identity: 'Beda identitas',
    revision_requested: 'Perlu revisi',
    review: 'Menunggu review',
};

export default function StatusBadge({ status, type = 'general', className = '' }: StatusBadgeProps) {
    if (type === 'loan') {
        if (status === 'completed') {
            return (
                <span
                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap shadow-2xs bg-emerald-50 text-emerald-800 border border-emerald-200 ${className}`}
                >
                    <span className="h-1.5 w-1.5 rounded-full bg-emerald-500" />
                    <span>Selesai</span>
                </span>
            );
        }

        if (status === 'rejected') {
            return (
                <span
                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap shadow-2xs bg-rose-50 text-rose-700 border border-rose-200 ${className}`}
                >
                    <span className="h-1.5 w-1.5 rounded-full bg-rose-500" />
                    <span>Ditolak</span>
                </span>
            );
        }

        if (status === 'cancelled') {
            return (
                <span
                    className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap shadow-2xs bg-slate-100 text-slate-600 border border-slate-200 ${className}`}
                >
                    <span className="h-1.5 w-1.5 rounded-full bg-slate-400" />
                    <span>Dibatalkan</span>
                </span>
            );
        }

        // Ongoing / In-Progress Loans: show 'Dalam Proses' (kuning) + Stage info
        const config = LOAN_STAGE_CONFIGS[status] || {
            label: 'Tahap 3: BAST & Serah Fisik',
            color: 'bg-indigo-50 text-indigo-700 border border-indigo-200',
            dot: 'bg-indigo-500',
        };

        return (
            <div className={`inline-flex items-center gap-1.5 flex-wrap ${className}`}>
                <span className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap shadow-2xs bg-amber-50 text-amber-800 border border-amber-200">
                    <span className="h-1.5 w-1.5 rounded-full bg-amber-500" />
                    <span>Dalam Proses</span>
                </span>
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold whitespace-nowrap shadow-2xs ${config.color}`}>
                    <span className={`h-1.5 w-1.5 rounded-full ${config.dot}`} />
                    <span>{config.label}</span>
                </span>
            </div>
        );
    }

    const color = ['rejected', 'missing', 'damaged', 'error', 'needs_repair'].includes(status)
        ? 'bg-amber-50 text-amber-900 border border-amber-200'
        : ['completed', 'returned', 'verified', 'resolved', 'found', 'committed'].includes(status)
        ? 'bg-emerald-50 text-emerald-800'
        : 'bg-slate-100 text-slate-700';

    return (
        <span className={`inline-block rounded-full px-3 py-1 text-xs font-semibold ${color} ${className}`}>
            {generalLabels[status] || workflowLabels[status] || status}
        </span>
    );
}

export function LoanStatusBadge({ status, className = '' }: { status: string; className?: string }) {
    return <StatusBadge status={status} type="loan" className={className} />;
}
