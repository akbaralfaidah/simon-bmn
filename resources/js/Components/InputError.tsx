import { HTMLAttributes, useEffect, useRef } from 'react';
import gsap from 'gsap';
import { AlertCircle } from 'lucide-react';

interface InputErrorProps extends HTMLAttributes<HTMLDivElement> {
    message?: string;
}

export default function InputError({
    message,
    className = '',
    ...props
}: InputErrorProps) {
    const errorRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (message && errorRef.current) {
            // Animasi getar / wiggle menggunakan GSAP saat error muncul
            gsap.fromTo(
                errorRef.current,
                { x: -8, opacity: 0.7 },
                {
                    x: 0,
                    opacity: 1,
                    duration: 0.45,
                    ease: 'elastic.out(1.2, 0.4)',
                    clearProps: 'transform',
                }
            );
        }
    }, [message]);

    if (!message) {
        return null;
    }

    return (
        <div
            ref={errorRef}
            {...props}
            role="alert"
            className={`mt-1.5 flex items-start gap-2.5 rounded-lg border border-red-200/90 bg-red-50/95 px-3 py-2 text-xs text-red-900 shadow-2xs transition-all duration-150 sm:text-xs ${className}`}
        >
            <div className="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600">
                <AlertCircle className="h-3.5 w-3.5" />
            </div>
            <div className="min-w-0 flex-1 leading-relaxed">
                <span className="font-semibold text-red-950 block sm:inline sm:mr-1">
                    Gagal:
                </span>
                <span className="text-red-800 break-words font-medium">
                    {message}
                </span>
            </div>
        </div>
    );
}

