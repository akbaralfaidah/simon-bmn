import { Eye, EyeOff } from 'lucide-react';
import {
    forwardRef,
    InputHTMLAttributes,
    useEffect,
    useImperativeHandle,
    useRef,
    useState,
} from 'react';

export default forwardRef(function TextInput(
    {
        type = 'text',
        className = '',
        isFocused = false,
        isError = false,
        ...props
    }: InputHTMLAttributes<HTMLInputElement> & { isFocused?: boolean; isError?: boolean },
    ref,
) {
    const localRef = useRef<HTMLInputElement>(null);
    const [showPassword, setShowPassword] = useState(false);

    useImperativeHandle(ref, () => ({
        focus: () => localRef.current?.focus(),
    }));

    useEffect(() => {
        if (isFocused) {
            localRef.current?.focus();
        }
    }, [isFocused]);

    const isPassword = type === 'password';
    const inputType = isPassword && showPassword ? 'text' : type;

    return (
        <div className="relative w-full">
            <input
                {...props}
                type={inputType}
                className={`simon-input ${isError ? '!border-danger !focus:border-danger !focus:ring-danger/20' : ''} ${className} ${isPassword ? '!pr-10' : ''}`}
                ref={localRef}
            />
            {isPassword && (
                <button
                    type="button"
                    onClick={() => setShowPassword(!showPassword)}
                    className="absolute inset-y-1 right-1 flex w-8 items-center justify-center text-slate-400 rounded-md transition-all duration-150 ease-out hover:text-slate-600 hover:bg-slate-100/80 active:scale-95 focus:text-primary focus:outline-none"
                    aria-pressed={showPassword}
                    aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                >
                    {showPassword ? (
                        <EyeOff className="w-3.5 h-3.5 text-slate-400" />
                    ) : (
                        <Eye className="w-3.5 h-3.5 text-slate-400" />
                    )}
                </button>
            )}
        </div>
    );
});
