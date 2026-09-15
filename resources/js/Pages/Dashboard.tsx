import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, usePage } from '@inertiajs/react';
import { Package, CheckCircle, AlertTriangle, Wallet, BarChart3, TrendingUp } from 'lucide-react';
import { cn } from '@/lib/utils';

export default function Dashboard({ stats, categories }: any) {
    const user = usePage().props.auth.user as any;

    const statCards = [
        { 
            name: 'Total Aset Terdaftar', 
            value: stats.total.toLocaleString('id-ID'), 
            icon: Package, 
            color: 'text-brand-primary', 
            bg: 'bg-brand-primary/10',
            border: 'border-brand-primary/20'
        },
        { 
            name: 'Kondisi Baik', 
            value: stats.kondisi_baik.toLocaleString('id-ID'), 
            icon: CheckCircle, 
            color: 'text-emerald-600', 
            bg: 'bg-emerald-50',
            border: 'border-emerald-200'
        },
        { 
            name: 'Perlu Perhatian', 
            value: stats.kondisi_rusak.toLocaleString('id-ID'), 
            icon: AlertTriangle, 
            color: 'text-red-600', 
            bg: 'bg-red-50',
            border: 'border-red-200'
        },
        { 
            name: 'Total Nilai Perolehan', 
            value: 'Rp ' + (stats.total_value / 1000000).toFixed(0) + ' Jt', 
            icon: Wallet, 
            color: 'text-brand-secondary', 
            bg: 'bg-brand-secondary/10',
            border: 'border-brand-secondary/20'
        },
    ];

    // Calculate max for bar chart
    const maxCount = Math.max(...categories.map((c: any) => c.assets_count), 1);

    return (
        <AuthenticatedLayout header="Ringkasan Dasbor">
            <Head title="Dashboard" />

            <div className="max-w-7xl mx-auto space-y-6">
                {/* Welcome Banner */}
                <div className="bg-gradient-to-r from-brand-primaryDark via-brand-primary to-brand-primaryLight rounded-2xl p-8 text-white shadow-xl relative overflow-hidden">
                    <div className="relative z-10">
                        <h1 className="text-3xl font-bold mb-2 tracking-tight">Selamat Datang, {user.name}!</h1>
                        <p className="text-white/80 max-w-xl leading-relaxed text-sm">
                            Sistem Informasi Manajemen Barang Milik Negara — Balai Penegakan Hukum Lingkungan Hidup dan Kehutanan Wilayah Sumatera.
                            Saat ini tercatat <span className="font-bold text-brand-secondaryLight">{stats.total} unit aset</span> senilai total <span className="font-bold text-brand-secondaryLight">Rp {(stats.total_value / 1000000).toFixed(0)} Juta</span>.
                        </p>
                    </div>
                    <div className="absolute -right-10 -top-24 w-64 h-64 bg-white/10 rounded-full blur-3xl" />
                    <div className="absolute right-32 -bottom-24 w-48 h-48 bg-brand-secondary/20 rounded-full blur-2xl" />
                </div>

                {/* Stat Cards */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                    {statCards.map((stat) => (
                        <div key={stat.name} className={cn("bg-white rounded-2xl p-5 shadow-sm border hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1", stat.border)}>
                            <div className="flex items-center justify-between mb-3">
                                <div className={cn("h-11 w-11 rounded-xl flex items-center justify-center", stat.bg)}>
                                    <stat.icon className={cn("h-5 w-5", stat.color)} />
                                </div>
                                <TrendingUp className="h-4 w-4 text-gray-300" />
                            </div>
                            <p className="text-xs font-medium text-gray-500 mb-1">{stat.name}</p>
                            <h3 className="text-2xl font-bold text-gray-900 tracking-tight">{stat.value}</h3>
                        </div>
                    ))}
                </div>

                {/* Category Distribution */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div className="flex items-center gap-3 mb-6">
                            <div className="h-10 w-10 rounded-xl bg-brand-primary/10 flex items-center justify-center">
                                <BarChart3 className="h-5 w-5 text-brand-primary" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">Distribusi per Kategori</h3>
                                <p className="text-xs text-gray-500">Jumlah aset berdasarkan klasifikasi barang</p>
                            </div>
                        </div>
                        <div className="space-y-4">
                            {categories
                                .filter((c: any) => c.assets_count > 0)
                                .sort((a: any, b: any) => b.assets_count - a.assets_count)
                                .map((cat: any) => (
                                <div key={cat.id}>
                                    <div className="flex justify-between text-sm mb-1.5">
                                        <span className="font-medium text-gray-700">{cat.name}</span>
                                        <span className="font-bold text-gray-900">{cat.assets_count}</span>
                                    </div>
                                    <div className="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
                                        <div 
                                            className="h-full rounded-full bg-gradient-to-r from-brand-primary to-brand-primaryLight transition-all duration-700"
                                            style={{ width: `${(cat.assets_count / maxCount) * 100}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Quick Info */}
                    <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                        <div className="flex items-center gap-3 mb-6">
                            <div className="h-10 w-10 rounded-xl bg-brand-secondary/10 flex items-center justify-center">
                                <Package className="h-5 w-5 text-brand-secondary" />
                            </div>
                            <div>
                                <h3 className="text-lg font-bold text-gray-900">Ringkasan Kondisi</h3>
                                <p className="text-xs text-gray-500">Rasio kesehatan aset BMN saat ini</p>
                            </div>
                        </div>

                        <div className="flex items-center justify-center py-8">
                            <div className="relative">
                                {/* Simple donut visualization */}
                                <svg className="w-48 h-48 -rotate-90" viewBox="0 0 120 120">
                                    <circle cx="60" cy="60" r="50" fill="none" stroke="#f0f0f0" strokeWidth="12" />
                                    <circle 
                                        cx="60" cy="60" r="50" fill="none" stroke="#015850" strokeWidth="12"
                                        strokeDasharray={`${(stats.kondisi_baik / stats.total) * 314} 314`}
                                        strokeLinecap="round"
                                        className="transition-all duration-1000"
                                    />
                                </svg>
                                <div className="absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-3xl font-bold text-gray-900">
                                        {stats.total > 0 ? Math.round((stats.kondisi_baik / stats.total) * 100) : 0}%
                                    </span>
                                    <span className="text-xs text-gray-500">Kondisi Baik</span>
                                </div>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4 mt-2">
                            <div className="bg-emerald-50 rounded-xl p-3 text-center border border-emerald-100">
                                <p className="text-xl font-bold text-emerald-700">{stats.kondisi_baik}</p>
                                <p className="text-xs text-emerald-600 font-medium">Kondisi Baik</p>
                            </div>
                            <div className="bg-red-50 rounded-xl p-3 text-center border border-red-100">
                                <p className="text-xl font-bold text-red-700">{stats.kondisi_rusak}</p>
                                <p className="text-xs text-red-600 font-medium">Rusak / Perhatian</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
