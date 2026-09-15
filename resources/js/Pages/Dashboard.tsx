import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { Package, ShieldAlert, CheckCircle, Clock } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function Dashboard() {
    const user = usePage().props.auth.user as any;

    const stats = [
        { name: 'Total Aset Terdaftar', value: '1,240', icon: Package, color: 'text-brand-primary', bg: 'bg-brand-primary/10' },
        { name: 'Menunggu Persetujuan', value: '12', icon: Clock, color: 'text-brand-secondary', bg: 'bg-brand-secondary/10' },
        { name: 'Aset Dipinjam', value: '45', icon: CheckCircle, color: 'text-brand-informative', bg: 'bg-brand-informative/10' },
        { name: 'Laporan Kerusakan', value: '3', icon: ShieldAlert, color: 'text-red-600', bg: 'bg-red-100' },
    ];

    return (
        <AuthenticatedLayout header="Ringkasan Dasbor">
            <Head title="Dashboard" />

            <div className="max-w-7xl mx-auto space-y-6">
                {/* Welcome Banner */}
                <div className="bg-gradient-to-r from-brand-primaryDark to-brand-primary rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div className="relative z-10">
                        <h1 className="text-3xl font-bold mb-2 tracking-tight">Selamat Datang, {user.name}!</h1>
                        <p className="text-white/80 max-w-xl leading-relaxed text-sm">
                            Anda login sebagai <span className="font-semibold text-brand-secondaryLight">{user.roles?.[0]?.name || 'Pegawai'}</span>. Kelola dan pantau seluruh pergerakan Barang Milik Negara melalui dasbor terpusat SIMON Gakkum.
                        </p>
                    </div>
                    {/* Decorative shapes */}
                    <div className="absolute -right-10 -top-24 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
                    <div className="absolute right-32 -bottom-24 w-48 h-48 bg-brand-secondary/20 rounded-full blur-2xl" />
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    {stats.map((stat) => (
                        <div key={stat.name} className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                            <div className="flex items-center justify-between mb-4">
                                <div className={cn("h-12 w-12 rounded-xl flex items-center justify-center", stat.bg)}>
                                    <stat.icon className={cn("h-6 w-6", stat.color)} />
                                </div>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-gray-500 mb-1">{stat.name}</p>
                                <h3 className="text-3xl font-bold text-gray-900 tracking-tight">{stat.value}</h3>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Recent Activities Placeholder */}
                <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                    <h3 className="text-lg font-bold text-gray-900 mb-4">Aktivitas Terbaru</h3>
                    <div className="space-y-4">
                        {[1, 2, 3].map((i) => (
                            <div key={i} className="flex items-center gap-4 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50/50 rounded-lg px-2 transition-colors -mx-2">
                                <div className="h-10 w-10 rounded-full bg-brand-primary/10 flex items-center justify-center shrink-0">
                                    <Package className="h-5 w-5 text-brand-primary" />
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">Peminjaman Laptop Dinas diajukan</p>
                                    <p className="text-xs text-gray-500">2 jam yang lalu oleh Akbar Alfaidah</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
