import { ImgHTMLAttributes } from 'react';

export default function ApplicationLogo(props: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            {...props}
            src="/images/logo-gakkum.webp"
            alt="Logo BMN Gakkum"
        />
    );
}
