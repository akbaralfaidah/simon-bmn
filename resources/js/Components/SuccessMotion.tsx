import { useEffect, useRef, useState } from 'react';

export default function SuccessMotion() {
    const container = useRef<HTMLSpanElement>(null);
    const [ready, setReady] = useState(false);
    useEffect(() => {
        let cancelled = false;
        let cleanup: (() => void) | undefined;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        import('lottie-web/build/player/lottie_light').then(({ default: lottie }) => {
            if (cancelled || !container.current) return;
            const animation = lottie.loadAnimation({ container: container.current, renderer: 'svg', loop: false, autoplay: true, animationData: { v: '5.7.4', fr: 60, ip: 0, op: 12, w: 48, h: 48, nm: 'SIMON success', ddd: 0, assets: [], layers: [{ ty: 4, ind: 1, st: 0, ip: 0, op: 12, ks: { o: { a: 0, k: 100 }, r: { a: 0, k: 0 }, p: { a: 0, k: [0,0,0] }, a: { a: 0, k: [0,0,0] }, s: { a: 0, k: [100,100,100] } }, shapes: [{ ty: 'sh', ks: { a: 0, k: { i: [[0,0],[0,0],[0,0]], o: [[0,0],[0,0],[0,0]], v: [[10,24],[20,34],[38,14]], c: false } } }, { ty: 'st', c: { a: 0, k: [0.004,0.345,0.314,1] }, o: { a: 0, k: 100 }, w: { a: 0, k: 4 }, lc: 2, lj: 2 }, { ty: 'tm', s: { a: 0, k: 0 }, e: { a: 1, k: [{ t: 0, s: [0], e: [100], i: { x: [0.67], y: [1] }, o: { x: [0.33], y: [0] } }, { t: 12, s: [100] }] }, o: { a: 0, k: 0 }, m: 1 }] }] } });
            animation.addEventListener('DOMLoaded', () => setReady(true));
            cleanup = () => animation.destroy();
        }).catch(() => setReady(false));
        return () => { cancelled = true; cleanup?.(); };
    }, []);
    return <span className="relative block h-10 w-10" aria-hidden="true"><span className={ready ? 'hidden' : 'absolute inset-0 text-3xl text-brand-primary'}>✓</span><span ref={container} className="block h-10 w-10" /></span>;
}
