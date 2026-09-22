import { InputHTMLAttributes } from 'react';

export default function Checkbox({
    className = '',
    ...props
}: InputHTMLAttributes<HTMLInputElement>) {
    return (
        <input
            {...props}
            type="checkbox"
            className={
                'h-4 w-4 rounded border-slate-300 text-primary shadow-xs transition-all duration-150 ease-out active:scale-[0.98] focus:ring-2 focus:ring-primary/25 focus:ring-offset-0 ' +
                className
            }
        />
    );
}
