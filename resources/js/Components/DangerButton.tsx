import { ButtonHTMLAttributes } from 'react';
import { Loader2 } from 'lucide-react';

interface DangerButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    isLoading?: boolean;
}

export default function DangerButton({
    className = '',
    disabled,
    isLoading = false,
    children,
    ...props
}: DangerButtonProps) {
    return (
        <button
            {...props}
            aria-busy={isLoading}
            className={`simon-button-danger ${className}`}
            disabled={disabled || isLoading}
        >
            {isLoading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {children}
        </button>
    );
}
