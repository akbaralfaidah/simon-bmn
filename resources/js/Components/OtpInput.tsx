import { useEffect, useRef, useState, KeyboardEvent, ClipboardEvent } from 'react';

interface OtpInputProps {
    length?: number;
    value: string;
    onChange: (value: string) => void;
    hasError?: boolean;
    disabled?: boolean;
    autoFocus?: boolean;
}

export default function OtpInput({
    length = 6,
    value,
    onChange,
    hasError = false,
    disabled = false,
    autoFocus = true,
}: OtpInputProps) {
    const inputRefs = useRef<(HTMLInputElement | null)[]>([]);
    const [activeIndex, setActiveIndex] = useState<number>(0);
    const digits = (value || '').padEnd(length, ' ').slice(0, length).split('');

    useEffect(() => {
        if (autoFocus && inputRefs.current[0] && !disabled) {
            inputRefs.current[0]?.focus();
        }
    }, [autoFocus, disabled]);

    const handleKeyDown = (index: number, e: KeyboardEvent<HTMLInputElement>) => {
        if (e.key === 'Backspace') {
            e.preventDefault();
            const currentDigit = digits[index]?.trim();
            if (currentDigit) {
                const newDigits = [...digits];
                newDigits[index] = '';
                onChange(newDigits.join('').trim());
            } else if (index > 0) {
                const newDigits = [...digits];
                newDigits[index - 1] = '';
                onChange(newDigits.join('').trim());
                inputRefs.current[index - 1]?.focus();
                setActiveIndex(index - 1);
            }
        } else if (e.key === 'ArrowLeft' && index > 0) {
            e.preventDefault();
            inputRefs.current[index - 1]?.focus();
            setActiveIndex(index - 1);
        } else if (e.key === 'ArrowRight' && index < length - 1) {
            e.preventDefault();
            inputRefs.current[index + 1]?.focus();
            setActiveIndex(index + 1);
        }
    };

    const handleInput = (index: number, char: string) => {
        const cleanDigit = char.replace(/\D/g, '').slice(-1);
        if (!cleanDigit) return;

        const newDigits = [...digits.map(d => d.trim())];
        newDigits[index] = cleanDigit;
        const result = newDigits.join('');
        onChange(result);

        if (index < length - 1) {
            inputRefs.current[index + 1]?.focus();
            setActiveIndex(index + 1);
        }
    };

    const handlePaste = (e: ClipboardEvent<HTMLInputElement>) => {
        e.preventDefault();
        const pastedData = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, length);
        if (pastedData) {
            onChange(pastedData);
            const focusTarget = Math.min(pastedData.length, length - 1);
            inputRefs.current[focusTarget]?.focus();
            setActiveIndex(focusTarget);
        }
    };

    const isComplete = value.length === length;

    return (
        <div 
            className={`flex items-center justify-center gap-2 sm:gap-3 my-2 ${hasError ? 'animate-shake' : ''}`}
            role="group"
            aria-label="Kode verifikasi 6 digit"
        >
            {Array.from({ length }).map((_, index) => {
                const digit = digits[index]?.trim() || '';
                const isFocused = activeIndex === index;
                return (
                    <input
                        key={index}
                        ref={el => { inputRefs.current[index] = el; }}
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]*"
                        autoComplete="one-time-code"
                        maxLength={1}
                        disabled={disabled}
                        value={digit}
                        onFocus={() => setActiveIndex(index)}
                        onChange={e => handleInput(index, e.target.value)}
                        onKeyDown={e => handleKeyDown(index, e)}
                        onPaste={handlePaste}
                        aria-label={`Digit ke-${index + 1} dari ${length}`}
                        className={`h-12 w-11 sm:h-13 sm:w-12 text-center text-lg sm:text-xl font-bold font-mono rounded-lg border transition-all duration-150 ease-out focus:outline-none ${
                            hasError
                                ? 'border-red-500 bg-red-50/50 text-red-900 focus:ring-2 focus:ring-red-500/20'
                                : isComplete
                                ? 'border-primary bg-primary-50/30 text-primary shadow-xs ring-1 ring-primary/30'
                                : isFocused
                                ? 'border-primary ring-2 ring-primary/20 scale-[1.03] bg-white text-slate-900 shadow-xs'
                                : digit
                                ? 'border-slate-300 bg-white text-slate-900'
                                : 'border-slate-300 bg-slate-50/60 text-slate-400'
                        } disabled:opacity-50 disabled:cursor-not-allowed`}
                    />
                );
            })}
        </div>
    );
}
