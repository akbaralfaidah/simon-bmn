import { ReactNode } from 'react';
import { DialogTitle } from '@headlessui/react';
import Modal from './Modal';
import PrimaryButton from './PrimaryButton';
import SecondaryButton from './SecondaryButton';
import SuccessMotion from './SuccessMotion';
import { CheckCircle2, AlertTriangle, XCircle, Info } from 'lucide-react';

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
                return <CheckCircle2 className="h-6 w-6 text-green-600" />;
            case 'warning':
                return <AlertTriangle className="h-6 w-6 text-amber-500" />;
            case 'error':
                return <XCircle className="h-6 w-6 text-red-600" />;
            default:
                return <Info className="h-6 w-6 text-blue-600" />;
        }
    };

    return (
        <Modal show={show} onClose={onClose} maxWidth="sm">
            <div className="p-6 text-center">
                <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 mb-4">
                    {type === 'success' ? <SuccessMotion /> : renderIcon()}
                </div>
                <DialogTitle className="text-lg font-semibold text-dark mb-2">{title}</DialogTitle>
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
