import { Loader2 } from 'lucide-react';

interface SectionLoaderProps {
    message?: string;
    variant?: 'spinner' | 'skeleton';
    rows?: number;
    className?: string;
    overlay?: boolean;
}

export default function SectionLoader({
    message = 'Memuat data...',
    variant = 'spinner',
    rows = 4,
    className = '',
    overlay = false,
}: SectionLoaderProps) {
    if (variant === 'skeleton') {
        return (
            <div className={`space-y-3 w-full animate-shimmer ${className}`} aria-busy="true" aria-label={message}>
                <div className="h-6 bg-slate-200/80 rounded-md w-1/3 mb-4"></div>
                {Array.from({ length: rows }).map((_, idx) => (
                    <div key={idx} className="flex items-center gap-4 py-2 border-b border-slate-100 last:border-b-0">
                        <div className="h-10 w-10 rounded-lg bg-slate-200/70 shrink-0"></div>
                        <div className="flex-1 space-y-2">
                            <div className="h-4 bg-slate-200/80 rounded w-3/4"></div>
                            <div className="h-3 bg-slate-100 rounded w-1/2"></div>
                        </div>
                        <div className="h-8 w-20 bg-slate-100 rounded-md shrink-0"></div>
                    </div>
                ))}
            </div>
        );
    }

    const content = (
        <div className="flex flex-col items-center justify-center p-8 text-center">
            <div className="relative flex items-center justify-center w-12 h-12 mb-3">
                <div className="absolute inset-0 bg-primary-50 rounded-full flex items-center justify-center animate-pulse-glow">
                    <img src="/images/logo-gakkum.webp" alt="Loading" className="w-6 h-6 object-contain opacity-75" />
                </div>
                <Loader2 className="animate-spin text-primary w-12 h-12 absolute inset-0 stroke-[1.75]" />
            </div>
            <p className="text-sm font-medium text-slate-700">{message}</p>
            <span className="text-[11px] text-slate-400 mt-0.5">Sistem Operasional SIMON BMN</span>
        </div>
    );

    if (overlay) {
        return (
            <div className={`absolute inset-0 z-20 flex items-center justify-center bg-white/80 backdrop-blur-xs rounded-xl ${className}`} aria-busy="true">
                {content}
            </div>
        );
    }

    return (
        <div className={`w-full flex items-center justify-center min-h-[200px] ${className}`} aria-busy="true">
            {content}
        </div>
    );
}
