import { PropsWithChildren, useEffect, useRef } from 'react';
import { usePage } from '@inertiajs/react';

export default function PageMotion({ children }: PropsWithChildren) {
    const element = useRef<HTMLDivElement>(null);
    const { url } = usePage();
    useEffect(() => {
        let cancelled = false;
        let revert: (() => void) | undefined;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        import('gsap').then(({ gsap }) => {
            if (cancelled || !element.current) return;
            const animation = gsap.fromTo(element.current, { opacity: 0.85, y: 4 }, { opacity: 1, y: 0, duration: 0.18, clearProps: 'all' });
            revert = () => animation.revert();
        }).catch(() => { /* Content remains visible when optional animation cannot load. */ });
        return () => { cancelled = true; revert?.(); };
    }, [url]);
    return <div ref={element}>{children}</div>;
}
