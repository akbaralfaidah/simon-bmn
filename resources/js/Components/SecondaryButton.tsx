import { ButtonHTMLAttributes } from 'react';
import { Loader2 } from 'lucide-react';

interface SecondaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    isLoading?: boolean;
}

export default function SecondaryButton({
    type = 'button',
    className = '',
    disabled,
    isLoading = false,
    children,
    ...props
}: SecondaryButtonProps) {
    return (
        <button
            {...props}
            type={type}
            aria-busy={isLoading}
            className={`simon-button-secondary ${className}`}
            disabled={disabled || isLoading}
        >
            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {children}
        </button>
    );
}
