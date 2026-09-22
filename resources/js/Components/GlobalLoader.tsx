import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';
import { Transition, TransitionChild } from '@headlessui/react';

export default function GlobalLoader() {
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState('Memuat data...');

    useEffect(() => {
        let timeout: NodeJS.Timeout;

        // Listen to Inertia events
        const startListener = router.on('start', (event) => {
            const method = event.detail.visit.method?.toLowerCase() || 'get';

            // Mutating requests (POST, PUT, PATCH, DELETE) always show Notif Gakkum
            if (method !== 'get') {
                setMessage((prev) => (prev && prev !== 'Memuat data...' ? prev : 'Memproses data...'));
                setLoading(true);
                return;
            }

            // For GET visits, don't show global loader for silent/partial visits
            if (event.detail.visit.preserveState && event.detail.visit.preserveScroll) return;

            setMessage('Memuat data...');
            setLoading(true);
        });

        const finishListener = router.on('finish', () => {
            timeout = setTimeout(() => {
                setLoading(false);
            }, 200);
        });

        // Listen for custom global events
        const handleCustomStart = (e: CustomEvent) => {
            if (e.detail?.message) {
                setMessage(e.detail.message);
            } else {
                setMessage('Memproses data...');
            }
            setLoading(true);
        };

        const handleCustomStop = () => {
            timeout = setTimeout(() => {
                setLoading(false);
            }, 150);
        };

        window.addEventListener('global-load-start', handleCustomStart as EventListener);
        window.addEventListener('global-load-stop', handleCustomStop);

        return () => {
            startListener();
            finishListener();
            window.removeEventListener('global-load-start', handleCustomStart as EventListener);
            window.removeEventListener('global-load-stop', handleCustomStop);
            clearTimeout(timeout);
        };
    }, []);

    return (
        <Transition show={loading} as="div" className="fixed inset-0 z-[100] pointer-events-none">
            <TransitionChild
                enter="transition-opacity ease-out duration-250"
                enterFrom="opacity-0"
                enterTo="opacity-100"
                leave="transition-opacity ease-in duration-200"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
            >
                {/* Backdrop: Semi-transparent backdrop with blur */}
                <div className="fixed inset-0 bg-slate-900/35 backdrop-blur-xs pointer-events-auto" />
            </TransitionChild>

            <div className="fixed inset-0 flex items-center justify-center p-4" role="status" aria-live="polite">
                <TransitionChild
                    enter="transition ease-[cubic-bezier(0.23,1,0.32,1)] duration-300 transform"
                    enterFrom="opacity-0 scale-90 translate-y-3"
                    enterTo="opacity-100 scale-100 translate-y-0"
                    leave="transition ease-in duration-200 transform"
                    leaveFrom="opacity-100 scale-100 translate-y-0"
                    leaveTo="opacity-0 scale-95 translate-y-2"
                >
                    <div className="pointer-events-auto bg-white/95 backdrop-blur-md rounded-2xl shadow-[0_16px_48px_rgba(0,0,0,0.18)] border border-slate-200/90 p-6 flex flex-col items-center justify-center min-w-[240px] max-w-sm text-center">
                        <div className="relative flex items-center justify-center w-14 h-14 mb-3.5">
                            {/* Inner logo with gentle pulse glow */}
                            <div className="absolute inset-1 bg-gradient-to-br from-primary-50 to-primary-100 rounded-full flex items-center justify-center shadow-inner">
                                <img src="/images/logo-gakkum.webp" alt="Gakkum" className="w-7 h-7 object-contain opacity-90 drop-shadow-xs" />
                            </div>
                            {/* Primary spinning outer ring */}
                            <svg className="animate-spin text-primary w-14 h-14 absolute inset-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-15" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2.5"></circle>
                                <path className="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {/* Subtle gold accent indicator dot */}
                            <div className="absolute top-0 right-1 w-2.5 h-2.5 rounded-full bg-accent ring-2 ring-white shadow-xs"></div>
                        </div>
                        <span className="text-sm font-semibold tracking-tight text-slate-800 line-clamp-2 px-2">{message}</span>
                        <span className="text-[10px] text-slate-400 font-medium tracking-wide mt-1 uppercase">SIMON GAKKUM SUMATERA</span>
                    </div>
                </TransitionChild>
            </div>
        </Transition>
    );
}
