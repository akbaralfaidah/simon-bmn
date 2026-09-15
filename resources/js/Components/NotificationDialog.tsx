import { ReactNode } from 'react';
import Modal from './Modal';
import PrimaryButton from './PrimaryButton';
import SecondaryButton from './SecondaryButton';

interface NotificationDialogProps {
    show: boolean;
    type?: 'success' | 'warning' | 'error' | 'info';
    icon?: ReactNode;
    title: string;
    description: string;
    objectReference?: string;
    primaryActionText?: string;
    secondaryActionText?: string;
    onPrimaryAction?: () => void;
    onSecondaryAction?: () => void;
    onClose: () => void;
}

export default function NotificationDialog({
    show,
    type = 'info',
    icon,
    title,
    description,
    objectReference,
    primaryActionText,
    secondaryActionText,
    onPrimaryAction,
    onSecondaryAction,
    onClose,
}: NotificationDialogProps) {
    
    const renderIcon = () => {
        if (icon) return icon;
        switch (type) {
            case 'success':
                return <svg className="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>;
            case 'warning':
                return <svg className="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2.25m-6.364.386l1.363-1.364m10.607 1.364l-1.363-1.364M12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>;
            case 'error':
                return <svg className="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>;
            default:
                return <svg className="h-6 w-6 text-info" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>;
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <div className="p-6 text-center">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 mb-4">
                    {renderIcon()}
                </div>
                <h3 className="text-lg font-semibold text-dark mb-2">{title}</h3>
                <div className="text-sm text-gray-500 mb-4">
                    {description}
                </div>
                {objectReference && (
                    <div className="mb-6 p-3 bg-gray-50 rounded-lg border border-gray-200 text-sm text-gray-700 font-medium">
                        {objectReference}
                    </div>
                )}
                
                <div className="mt-6 flex flex-col gap-3 sm:flex-row-reverse sm:justify-center">
                    {primaryActionText && (
                        <PrimaryButton
                            onClick={onPrimaryAction}
                            className="w-full sm:w-auto justify-center"
                        >
                            {primaryActionText}
                        </PrimaryButton>
                    )}
                    {secondaryActionText && (
                        <SecondaryButton
                            onClick={onSecondaryAction}
                            className="w-full sm:w-auto justify-center"
                        >
                            {secondaryActionText}
                        </SecondaryButton>
                    )}
                </div>
            </div>
        </Modal>
    );
}
