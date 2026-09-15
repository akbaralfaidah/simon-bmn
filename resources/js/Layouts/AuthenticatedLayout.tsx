import { Link, usePage } from '@inertiajs/react';
import { PropsWithChildren, ReactNode, useState } from 'react';
import { cn } from '@/lib/utils';
import { 
    LayoutDashboard, 
    PackageSearch, 
    ShoppingCart, 
    ClipboardCheck, 
    FileText, 
    Settings, 
    LogOut,
    Menu,
    X,
    User,
    Shield
} from 'lucide-react';
import Dropdown from '@/Components/Dropdown';

export default function Authenticated({
    header,
    children,
}: PropsWithChildren<{ header?: ReactNode }>) {
    const user = usePage().props.auth.user as any;
    const [sidebarOpen, setSidebarOpen] = useState(false);

    const navItems = [
        { name: 'Beranda', href: route('dashboard'), icon: LayoutDashboard, active: route().current('dashboard') },
        { name: 'Katalog Aset', href: route('assets.index'), icon: PackageSearch, active: route().current('assets.*') },
    ];

    return (
        <div className="min-h-screen bg-gray-50 flex font-sans">
            {/* Sidebar Mobile Overlay */}
            {sidebarOpen && (
                <div 
                    className="fixed inset-0 z-40 bg-black/50 lg:hidden backdrop-blur-sm"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Sidebar */}
            <aside className={cn(
                "fixed inset-y-0 left-0 z-50 w-72 bg-brand-primary text-white transition-transform duration-300 ease-in-out lg:static lg:translate-x-0 flex flex-col shadow-2xl lg:shadow-none",
                sidebarOpen ? "translate-x-0" : "-translate-x-full"
            )}>
                {/* Brand / Logo */}
                <div className="h-20 flex items-center justify-between px-6 border-b border-white/10 shrink-0 bg-brand-primaryDark">
                    <Link href="/" className="flex items-center gap-4">
                        <div className="h-10 w-10 bg-white rounded-lg flex items-center justify-center p-1 shadow-inner">
                            <img src="/images/logo-gakkum.webp" alt="Logo" className="h-full w-full object-contain" />
                        </div>
                        <div className="flex flex-col">
                            <span className="font-bold text-lg leading-tight tracking-wide text-white">SIMON</span>
                            <span className="text-[10px] text-brand-secondary font-semibold uppercase tracking-wider">Gakkum Sumatera</span>
                        </div>
                    </Link>
                    <button onClick={() => setSidebarOpen(false)} className="lg:hidden text-white/70 hover:text-white bg-white/5 p-1 rounded-md">
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Navigation */}
                <nav className="flex-1 overflow-y-auto py-6 px-4 space-y-1.5">
                    <div className="text-xs font-semibold text-white/40 uppercase tracking-wider mb-4 px-2">Menu Utama</div>
                    {navItems.map((item) => (
                        <Link
                            key={item.name}
                            href={item.href}
                            className={cn(
                                "flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 font-medium text-sm group",
                                item.active 
                                    ? "bg-brand-secondary text-white shadow-lg shadow-brand-secondary/30 ring-1 ring-white/20" 
                                    : "text-white/70 hover:bg-white/10 hover:text-white"
                            )}
                        >
                            <item.icon className={cn(
                                "h-5 w-5 transition-transform duration-200 group-hover:scale-110", 
                                item.active ? "text-white" : "text-brand-secondary"
                            )} />
                            {item.name}
                        </Link>
                    ))}
                </nav>

                {/* User Info & Logout */}
                <div className="p-5 border-t border-white/10 shrink-0 bg-brand-primaryDark/30">
                    <div className="flex items-center gap-3 mb-5">
                        <div className="h-11 w-11 rounded-full bg-gradient-to-br from-brand-secondary to-brand-secondaryDark flex items-center justify-center shrink-0 shadow-inner ring-2 ring-white/10">
                            <User className="h-5 w-5 text-white" />
                        </div>
                        <div className="overflow-hidden">
                            <p className="text-sm font-semibold truncate text-white">{user.name}</p>
                            <p className="text-xs text-white/50 truncate flex items-center gap-1 mt-0.5">
                                <Shield className="h-3 w-3 text-brand-secondary" />
                                {user.email}
                            </p>
                        </div>
                    </div>
                    <Link
                        href={route('logout')}
                        method="post"
                        as="button"
                        className="flex w-full items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white/80 hover:text-white bg-white/5 hover:bg-red-500/80 rounded-xl transition-all duration-200 border border-white/5"
                    >
                        <LogOut className="h-4 w-4" />
                        Keluar
                    </Link>
                </div>
            </aside>

            {/* Main Content */}
            <div className="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">
                {/* Top Header */}
                <header className="h-20 bg-white border-b border-gray-200 flex items-center justify-between px-4 lg:px-10 shrink-0 shadow-sm z-10">
                    <div className="flex items-center gap-4">
                        <button 
                            onClick={() => setSidebarOpen(true)}
                            className="lg:hidden text-gray-500 hover:text-brand-primary bg-gray-100 hover:bg-gray-200 p-2 rounded-lg transition-colors"
                        >
                            <Menu className="h-5 w-5" />
                        </button>
                        {header && (
                            <div className="text-xl font-bold text-gray-800 hidden sm:block">
                                {header}
                            </div>
                        )}
                    </div>

                    <div className="flex items-center gap-4">
                        <Dropdown>
                            <Dropdown.Trigger>
                                <button className="flex items-center gap-3 text-sm font-medium text-gray-600 hover:text-brand-primary transition-colors bg-gray-50 hover:bg-gray-100 py-1.5 px-3 rounded-full border border-gray-200">
                                    <span>{user.name}</span>
                                    <div className="h-8 w-8 rounded-full bg-brand-primary/10 text-brand-primary flex items-center justify-center">
                                        <User className="h-4 w-4" />
                                    </div>
                                </button>
                            </Dropdown.Trigger>
                            <Dropdown.Content>
                                <Dropdown.Link href={route('profile.edit')}>Pengaturan Profil</Dropdown.Link>
                                <Dropdown.Link href={route('logout')} method="post" as="button">Keluar</Dropdown.Link>
                            </Dropdown.Content>
                        </Dropdown>
                    </div>
                </header>

                {/* Page Content */}
                <main className="flex-1 overflow-y-auto p-4 lg:p-10 bg-[#F4F7F6]">
                    {children}
                </main>
            </div>
        </div>
    );
}
