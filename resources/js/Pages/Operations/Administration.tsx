import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage } from '@inertiajs/react';
import ActionForm, { Field } from '@/Components/ActionForm';
import Pagination from '@/Components/Pagination';
import { PageProps } from '@/types';
import { useMemo, useState } from 'react';
import { 
    Users, 
    Building2, 
    DoorClosed, 
    Layers, 
    ShieldAlert, 
    CheckCircle2, 
    AlertCircle, 
    Clock, 
    Search, 
    ArrowRight,
    UserCheck,
    SlidersHorizontal,
    Plus,
    ChevronDown,
    ChevronUp,
    Briefcase,
    Trash2,
    Pencil
} from 'lucide-react';

interface UnitItem {
    id: number;
    name: string;
    code: string;
}

interface RoomItem {
    id: number;
    name: string;
    code: string;
    unit_id: number;
}

interface CategoryItem {
    id: number;
    name: string;
    code: string;
}

interface RoleAssignmentItem {
    id: number;
    role: { id: number; name: string };
    unit_id: number | null;
    room_id: number | null;
    starts_at: string;
    ends_at: string | null;
    is_global: boolean;
    can_administer: boolean;
}

interface UserItem {
    id: number;
    name: string;
    email: string;
    status: 'active' | 'pending' | 'suspended' | string;
    email_verified_at: string | null;
    profile?: {
        id: number;
        nip?: string;
        unit_id?: number;
        phone?: string;
        unit?: { id: number; name: string };
    } | null;
    role_assignments: RoleAssignmentItem[];
}

interface Props {
    users: {
        data: UserItem[];
        links: any[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    units: UnitItem[];
    rooms: RoomItem[];
    categories: CategoryItem[];
    isGlobal: boolean;
}

export default function Administration({ users, units, rooms, categories, isGlobal }: Props) {
    const { auth } = usePage<PageProps>().props;

    // Tabs: 'users' | 'organization'
    const [activeTab, setActiveTab] = useState<'users' | 'organization'>('users');
    
    // Organization Sub-Tab: 'units' | 'rooms' | 'categories'
    const [orgSubTab, setOrgSubTab] = useState<'all' | 'units' | 'rooms' | 'categories'>('all');

    // Users Search & Status Filter
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState<'all' | 'active' | 'pending' | 'suspended'>(() => {
        if (typeof window !== 'undefined') {
            const params = new URLSearchParams(window.location.search);
            const s = params.get('status');
            if (s === 'pending' || s === 'active' || s === 'suspended') {
                return s;
            }
        }
        return 'all';
    });
    
    // Expanded assignments on mobile / detail
    const [expandedUsers, setExpandedUsers] = useState<Record<number, boolean>>({});

    const toggleExpand = (userId: number) => {
        setExpandedUsers(prev => ({ ...prev, [userId]: !prev[userId] }));
    };

    // Filter users client-side for quick searching within the current page
    const filteredUsers = useMemo(() => {
        return users.data.filter(user => {
            const matchesSearch = 
                user.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                user.email.toLowerCase().includes(searchQuery.toLowerCase()) ||
                (user.profile?.nip && user.profile.nip.includes(searchQuery));
            
            const matchesStatus = statusFilter === 'all' || user.status === statusFilter;
            return matchesSearch && matchesStatus;
        });
    }, [users.data, searchQuery, statusFilter]);

    // Unit field definition for forms
    const unitField: Field = { 
        name: 'unit_id', 
        label: 'Unit penugasan', 
        type: 'select', 
        required: false, 
        options: units.map(u => ({ value: u.id, label: u.name })) 
    };

    // Fast mapping for unit lookup
    const unitMap = useMemo(() => {
        return new Map(units.map(u => [u.id, u.name]));
    }, [units]);

    // Fast mapping for room lookup
    const roomMap = useMemo(() => {
        return new Map(rooms.map(r => [r.id, r.name]));
    }, [rooms]);

    // Status counts
    const statusCounts = useMemo(() => {
        return {
            all: users.data.length,
            active: users.data.filter(u => u.status === 'active').length,
            pending: users.data.filter(u => u.status === 'pending').length,
            suspended: users.data.filter(u => u.status === 'suspended').length,
        };
    }, [users.data]);

    return (
        <AuthenticatedLayout 
            header={
                <div className="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="simon-badge-primary text-[11px] font-bold uppercase tracking-wider">
                                Pusat Kendali
                            </span>
                        </div>
                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-900">
                            Administrasi Akun & Master Data
                        </h1>
                    </div>
                    {isGlobal && (
                        <div className="mt-3 sm:mt-0">
                            <Link 
                                href={route('placement.index')} 
                                className="simon-button-secondary inline-flex items-center gap-2 text-xs font-semibold"
                            >
                                <span>Penataan Ruangan Aset</span>
                                <ArrowRight className="h-3.5 w-3.5 text-slate-500" />
                            </Link>
                        </div>
                    )}
                </div>
            }
        >
            <Head title="Administrasi" />

            {/* Context Info Banner */}
            <div className="mb-6 rounded-xl border border-slate-200/80 bg-gradient-to-r from-teal-50/60 via-slate-50 to-amber-50/40 p-4 text-xs leading-relaxed text-slate-600 shadow-xs">
                <div className="flex items-start gap-2.5">
                    <ShieldAlert className="mt-0.5 h-4 w-4 shrink-0 text-teal-700" />
                    <div>
                        <span className="font-semibold text-slate-800">Prinsip Mandat Administrasi:</span> Mandat administrasi terpisah dari role operasional harian. Aktivasi akun membutuhkan email terverifikasi. Pengubahan akun sendiri tidak diizinkan dan harus melalui pengelola lain.
                    </div>
                </div>
            </div>

            {/* Main Tabs Navigation */}
            <div className="mb-6 border-b border-slate-200">
                <div className="flex gap-2 overflow-x-auto pb-px">
                    <button
                        type="button"
                        onClick={() => setActiveTab('users')}
                        className={`group inline-flex items-center gap-2.5 border-b-2 px-4 py-3 text-sm font-semibold transition-all whitespace-nowrap ${
                            activeTab === 'users'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
                        }`}
                    >
                        <Users className={`h-4 w-4 transition-colors ${activeTab === 'users' ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'}`} />
                        <span>Pengguna & Hak Akses</span>
                        <span className={`ml-1.5 rounded-full px-2 py-0.5 text-[11px] font-bold ${
                            activeTab === 'users' ? 'bg-primary-50 text-primary border border-primary-200' : 'bg-slate-100 text-slate-600'
                        }`}>
                            {users.total ?? users.data.length}
                        </span>
                    </button>

                    <button
                        type="button"
                        onClick={() => setActiveTab('organization')}
                        className={`group inline-flex items-center gap-2.5 border-b-2 px-4 py-3 text-sm font-semibold transition-all whitespace-nowrap ${
                            activeTab === 'organization'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-800'
                        }`}
                    >
                        <Building2 className={`h-4 w-4 transition-colors ${activeTab === 'organization' ? 'text-primary' : 'text-slate-400 group-hover:text-slate-600'}`} />
                        <span>Organisasi & Master Data</span>
                        <span className={`ml-1.5 rounded-full px-2 py-0.5 text-[11px] font-bold ${
                            activeTab === 'organization' ? 'bg-primary-50 text-primary border border-primary-200' : 'bg-slate-100 text-slate-600'
                        }`}>
                            {units.length + rooms.length + categories.length}
                        </span>
                    </button>
                </div>
            </div>

            {/* TAB 1: PENGGUNA & HAK AKSES */}
            {activeTab === 'users' && (
                <div className="space-y-4">
                    {/* Filter and Search Controls Toolbar */}
                    <div className="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs sm:flex-row sm:items-center sm:justify-between">
                        {/* Status Filter Buttons */}
                        <div className="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                onClick={() => setStatusFilter('all')}
                                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    statusFilter === 'all'
                                        ? 'bg-slate-900 text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                Semua ({statusCounts.all})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('active')}
                                className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    statusFilter === 'active'
                                        ? 'bg-emerald-700 text-white shadow-xs'
                                        : 'bg-emerald-50 text-emerald-800 border border-emerald-200/80 hover:bg-emerald-100'
                                }`}
                            >
                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                Aktif ({statusCounts.active})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('pending')}
                                className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    statusFilter === 'pending'
                                        ? 'bg-amber-700 text-white shadow-xs'
                                        : 'bg-amber-50 text-amber-800 border border-amber-200/80 hover:bg-amber-100'
                                }`}
                            >
                                <span className="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                Menunggu ({statusCounts.pending})
                            </button>
                            <button
                                type="button"
                                onClick={() => setStatusFilter('suspended')}
                                className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    statusFilter === 'suspended'
                                        ? 'bg-rose-700 text-white shadow-xs'
                                        : 'bg-rose-50 text-rose-800 border border-rose-200/80 hover:bg-rose-100'
                                }`}
                            >
                                <span className="h-1.5 w-1.5 rounded-full bg-rose-500"></span>
                                Ditangguhkan ({statusCounts.suspended})
                            </button>
                        </div>

                        {/* Search Bar */}
                        <div className="relative w-full sm:w-64">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                placeholder="Cari nama, email, NIP…"
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                className="simon-input !min-h-[34px] !py-1.5 !pl-8 text-xs"
                            />
                        </div>
                    </div>

                    {/* DESKTOP TABLE VIEW (md+) */}
                    <div className="hidden rounded-xl border border-slate-200 bg-white shadow-xs md:block overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-xs">
                                <thead className="border-b border-slate-200 bg-slate-50/80 font-semibold text-slate-700">
                                    <tr>
                                        <th className="px-4 py-3.5">Pegawai / Pengguna</th>
                                        <th className="px-4 py-3.5">Status Akun</th>
                                        <th className="px-4 py-3.5">Verifikasi Email</th>
                                        <th className="px-4 py-3.5">Unit & Mandat</th>
                                        <th className="px-4 py-3.5">Penugasan Aktif</th>
                                        <th className="px-4 py-3.5 text-right">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 text-slate-700">
                                    {filteredUsers.map(user => {
                                        const isSelf = user.id === auth.user.id;
                                        const initials = user.name
                                            .split(' ')
                                            .slice(0, 2)
                                            .map(n => n[0])
                                            .join('')
                                            .toUpperCase();

                                        const activeAssignments = user.role_assignments.filter(
                                            a => !a.ends_at || new Date(a.ends_at) > new Date()
                                        );

                                        return (
                                            <tr key={user.id} className="hover:bg-slate-50/60 transition-colors">
                                                {/* Pegawai / Pengguna */}
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-3">
                                                        <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-teal-100 font-bold text-teal-800 text-xs">
                                                            {initials}
                                                        </div>
                                                        <div className="min-w-0">
                                                            <div className="flex items-center gap-1.5">
                                                                <span className="font-semibold text-slate-900 truncate">
                                                                    {user.name}
                                                                </span>
                                                                {isSelf && (
                                                                    <span className="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold text-slate-700">
                                                                        Anda
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <div className="text-[11px] text-slate-500 truncate">
                                                                {user.email}
                                                            </div>
                                                            {(() => {
                                                                const nip = user.profile?.nip?.trim();
                                                                if (!nip || nip.includes('@')) return null;
                                                                return (
                                                                    <div className="text-[10px] text-slate-500 font-mono truncate font-medium">
                                                                        NIP. {nip}
                                                                    </div>
                                                                );
                                                            })()}
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Status Akun */}
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {user.status === 'active' && (
                                                        <span className="simon-badge-success text-[11px]">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                                            Aktif
                                                        </span>
                                                    )}
                                                    {user.status === 'pending' && (
                                                        <span className="simon-badge-warning text-[11px]">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-amber-600"></span>
                                                            Menunggu
                                                        </span>
                                                    )}
                                                    {user.status === 'suspended' && (
                                                        <span className="simon-badge-danger text-[11px]">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-rose-600"></span>
                                                            Ditangguhkan
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Verifikasi Email */}
                                                <td className="px-4 py-3 whitespace-nowrap">
                                                    {user.email_verified_at ? (
                                                        <span className="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200/60">
                                                            <CheckCircle2 className="h-3 w-3" />
                                                            Terverifikasi
                                                        </span>
                                                    ) : (
                                                        <span className="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200/60">
                                                            <AlertCircle className="h-3 w-3" />
                                                            Belum Verifikasi
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Unit & Mandat */}
                                                <td className="px-4 py-3">
                                                    <div className="space-y-1">
                                                        <div className="text-[11px] font-medium text-slate-800">
                                                            {user.profile?.unit?.name || (user.profile?.unit_id ? unitMap.get(user.profile.unit_id) : '—')}
                                                        </div>
                                                        <div className="flex flex-wrap gap-1">
                                                            {user.role_assignments.length > 0 ? (
                                                                user.role_assignments.map(a => (
                                                                    <span 
                                                                        key={a.id} 
                                                                        className={`rounded px-1.5 py-0.5 text-[10px] font-semibold border ${
                                                                            a.role.name === 'Koordinator'
                                                                                ? 'bg-purple-50 text-purple-700 border-purple-200'
                                                                                : a.role.name === 'Penanggung Jawab Ruangan'
                                                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                                                : 'bg-slate-100 text-slate-700 border-slate-200'
                                                                        }`}
                                                                    >
                                                                        {a.role.name}
                                                                    </span>
                                                                ))
                                                            ) : (
                                                                <span className="text-[11px] text-slate-400 italic">Belum ada role</span>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>

                                                {/* Penugasan Aktif & Revoke */}
                                                <td className="px-4 py-3">
                                                    {activeAssignments.length > 0 ? (
                                                        <div className="space-y-1.5">
                                                            {activeAssignments.map(assignment => {
                                                                const canManageAssignment = 
                                                                    !isSelf && 
                                                                    (!assignment.ends_at || new Date(assignment.ends_at) > new Date()) && 
                                                                    (isGlobal || (!assignment.is_global && !assignment.can_administer));

                                                                const assignmentUnitName = assignment.unit_id ? (unitMap.get(assignment.unit_id) || `Unit #${assignment.unit_id}`) : 'Lintas Unit (Global)';
                                                                const assignmentRoomName = assignment.room_id ? (roomMap.get(assignment.room_id) || `R.${assignment.room_id}`) : 'Semua Ruangan';

                                                                return (
                                                                    <div key={assignment.id} className="flex items-center justify-between gap-2 rounded bg-slate-50 p-1.5 border border-slate-200/60">
                                                                        <div className="text-[10px] text-slate-600 truncate max-w-[190px]">
                                                                            <span className="font-semibold text-slate-800">{assignment.role.name}</span>
                                                                            <div className="text-slate-500 truncate" title={`${assignmentUnitName} · ${assignmentRoomName}`}>
                                                                                {assignmentUnitName} · {assignmentRoomName}
                                                                            </div>
                                                                        </div>
                                                                        {canManageAssignment && (
                                                                            <div className="flex items-center gap-1 shrink-0">
                                                                                <ActionForm
                                                                                    title="Edit Mandat Penugasan"
                                                                                    url={route('administration.assignment.update', assignment.id)}
                                                                                    fields={[
                                                                                        {
                                                                                            name: 'role',
                                                                                            label: 'Peran / Mandat',
                                                                                            type: 'select',
                                                                                            required: true,
                                                                                            options: ['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'].map(value => ({ value, label: value }))
                                                                                        },
                                                                                        unitField,
                                                                                        {
                                                                                            name: 'room_id',
                                                                                            label: 'Ruangan (kosong = cakupan unit)',
                                                                                            type: 'select',
                                                                                            required: false,
                                                                                            options: rooms.map(r => ({ value: r.id, label: r.name }))
                                                                                        },
                                                                                        { name: 'ends_at', label: 'Batas akhir penugasan', type: 'date', required: false },
                                                                                        { name: 'reason', label: 'Alasan penyesuaian penugasan', type: 'textarea' },
                                                                                        { name: 'current_password', label: 'Password Anda (wajib saat peran Koordinator)', type: 'password', required: false }
                                                                                    ]}
                                                                                    initial={{
                                                                                        role: assignment.role.name,
                                                                                        unit_id: assignment.unit_id ?? '',
                                                                                        room_id: assignment.room_id ?? '',
                                                                                        ends_at: assignment.ends_at ? String(assignment.ends_at).substring(0, 10) : ''
                                                                                    }}
                                                                                    description="Perubahan mandat penugasan akan disimpan dan notifikasi email resmi akan dikirimkan ke pegawai."
                                                                                    buttonClassName="simon-button-secondary !min-h-[24px] !py-0.5 !px-2 text-[10px]"
                                                                                    label="Edit"
                                                                                />
                                                                                <ActionForm 
                                                                                    title="Cabut penugasan" 
                                                                                    url={route('administration.assignment.revoke', assignment.id)} 
                                                                                    fields={[
                                                                                        { name: 'reason', label: 'Alasan pencabutan / rencana pengganti', type: 'textarea' }, 
                                                                                        { name: 'current_password', label: 'Password Anda', type: 'password' }
                                                                                    ]} 
                                                                                    description="Akses mandat ini langsung berakhir dan sesi akun diakhiri. Notifikasi email pencabutan akan dikirimkan ke pegawai."
                                                                                    buttonClassName="simon-button-danger !min-h-[24px] !py-0.5 !px-2 text-[10px]"
                                                                                    label="Cabut"
                                                                                />
                                                                            </div>
                                                                        )}
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>
                                                    ) : (
                                                        <span className="text-[11px] text-slate-400 italic">Belum ada mandat aktif</span>
                                                    )}

                                                    {/* Optional Quick Add Mandate */}
                                                    {!isSelf && (isGlobal || (user.profile?.unit_id && unitMap.has(user.profile.unit_id))) && (
                                                        <div className="pt-1">
                                                            <ActionForm
                                                                title={`Tambah Mandat Penugasan: ${user.name}`}
                                                                description="Berikan peran atau penugasan tambahan untuk pegawai ini."
                                                                url={route('administration.user', user.id)}
                                                                fields={[
                                                                    { name: 'status', label: 'Status akun', type: 'hidden' },
                                                                    {
                                                                        name: 'role',
                                                                        label: 'Peran / Mandat Baru',
                                                                        type: 'select',
                                                                        required: true,
                                                                        options: ['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'].map(value => ({ value, label: value }))
                                                                    },
                                                                    unitField,
                                                                    {
                                                                        name: 'room_id',
                                                                        label: 'Ruangan (kosong = cakupan unit)',
                                                                        type: 'select',
                                                                        required: false,
                                                                        options: rooms.map(r => ({ value: r.id, label: r.name }))
                                                                    },
                                                                    { name: 'ends_at', label: 'Akhir penugasan', type: 'date', required: false },
                                                                    { name: 'reason', label: 'Alasan penambahan mandat', type: 'textarea' },
                                                                    { name: 'current_password', label: 'Password Anda (wajib saat peran Koordinator)', type: 'password', required: false }
                                                                ]}
                                                                initial={{ status: user.status, role: '', unit_id: user.profile?.unit_id ?? '', room_id: '', ends_at: '', reason: '' }}
                                                                buttonClassName="text-[10px] text-teal-700 hover:text-teal-900 font-semibold inline-flex items-center gap-0.5 hover:underline"
                                                                buttonContent={<><Plus className="h-2.5 w-2.5" /><span>Tambah Mandat</span></>}
                                                            />
                                                        </div>
                                                    )}
                                                </td>

                                                {/* Aksi */}
                                                <td className="px-4 py-3 text-right whitespace-nowrap">
                                                    {!isSelf ? (
                                                        <div className="flex items-center justify-end gap-1.5">
                                                            {user.status === 'pending' ? (
                                                                user.email_verified_at ? (
                                                                    <ActionForm 
                                                                        title={`Aktivasi Akun Pegawai: ${user.name}`} 
                                                                        description="Aktifkan akun pegawai ini agar dapat segera login ke sistem SIMON BMN. Unit penugasan telah tercatat saat pendaftaran."
                                                                        url={route('administration.user', user.id)} 
                                                                        fields={[
                                                                            { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                                            { name: 'reason', label: 'Catatan aktivasi (opsional)', type: 'textarea', required: false, hint: 'Opsional. Jika kosong, sistem otomatis mencatat aktivasi akun.' }
                                                                        ]} 
                                                                        initial={{ status: 'active', reason: '' }}
                                                                        buttonClassName="simon-button !min-h-[28px] !py-1 !px-2.5 text-xs inline-flex items-center gap-1 font-semibold shadow-xs"
                                                                        buttonContent={<><UserCheck className="h-3.5 w-3.5" /><span>Terima Akun</span></>}
                                                                    />
                                                                ) : (
                                                                    <span 
                                                                        className="inline-flex items-center gap-1 text-[11px] font-medium text-amber-700 bg-amber-50 px-2 py-1 rounded border border-amber-200/80 cursor-help"
                                                                        title="Pegawai belum memverifikasi email dinasnya. Akun baru dapat diaktifkan setelah email terverifikasi."
                                                                    >
                                                                        <Clock className="h-3 w-3 text-amber-600" />
                                                                        <span>Tunggu Email</span>
                                                                    </span>
                                                                )
                                                            ) : user.status === 'active' ? (
                                                                <ActionForm 
                                                                    title={`Tangguhkan Akun: ${user.name}`} 
                                                                    description="Sesi akun pengguna ini akan diakhiri dan pegawai tidak dapat login ke sistem hingga dipulihkan kembali."
                                                                    url={route('administration.user', user.id)} 
                                                                    fields={[
                                                                        { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                                        { name: 'reason', label: 'Alasan penangguhan akun', type: 'textarea', required: true, hint: 'Wajib diisi sebagai rekaman audit penonaktifan sementara.' }
                                                                    ]} 
                                                                    initial={{ status: 'suspended', reason: '' }}
                                                                    buttonClassName="simon-button-secondary !min-h-[28px] !py-1 !px-2 text-xs text-amber-700 hover:text-amber-800 border-amber-300 hover:bg-amber-50 inline-flex items-center gap-1"
                                                                    buttonContent={<><ShieldAlert className="h-3 w-3" /><span>Tangguhkan</span></>}
                                                                />
                                                            ) : (
                                                                <ActionForm 
                                                                    title={`Pulihkan Akun: ${user.name}`} 
                                                                    description="Status akun akan dipulihkan menjadi aktif sehingga pegawai dapat kembali login ke sistem."
                                                                    url={route('administration.user', user.id)} 
                                                                    fields={[
                                                                        { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                                        { name: 'reason', label: 'Catatan pemulihan (opsional)', type: 'textarea', required: false }
                                                                    ]} 
                                                                    initial={{ status: 'active', reason: '' }}
                                                                    buttonClassName="simon-button-secondary !min-h-[28px] !py-1 !px-2 text-xs text-emerald-700 hover:text-emerald-800 border-emerald-300 hover:bg-emerald-50 inline-flex items-center gap-1"
                                                                    buttonContent={<><CheckCircle2 className="h-3 w-3" /><span>Pulihkan</span></>}
                                                                />
                                                            )}

                                                            <ActionForm
                                                                title={`Edit Data Pegawai: ${user.name}`}
                                                                description="Perbarui informasi data nama lengkap, NIP resmi, atau nomor kontak dinas pegawai."
                                                                url={route('administration.user', user.id)}
                                                                fields={[
                                                                    { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                                    { name: 'name', label: 'Nama Lengkap & Gelar', type: 'text', required: true },
                                                                    { name: 'nip', label: 'NIP (kosongkan jika tidak ada)', type: 'text', required: false, hint: '18 digit angka NIP resmi (tanpa tanda @ atau email).' },
                                                                    { name: 'phone', label: 'Nomor WhatsApp / HP', type: 'text', required: false },
                                                                    unitField,
                                                                    { name: 'reason', label: 'Catatan perubahan data (opsional)', type: 'textarea', required: false }
                                                                ]}
                                                                initial={{
                                                                    status: user.status,
                                                                    name: user.name,
                                                                    nip: user.profile?.nip ?? '',
                                                                    phone: user.profile?.phone ?? '',
                                                                    unit_id: user.profile?.unit_id ?? '',
                                                                    reason: ''
                                                                }}
                                                                buttonClassName="simon-button-secondary !min-h-[28px] !py-1 !px-2 text-xs text-slate-700 hover:text-slate-900 border-slate-300 inline-flex items-center gap-1"
                                                                buttonContent={<><Pencil className="h-3 w-3" /><span>Edit</span></>}
                                                            />

                                                            <ActionForm
                                                                title={`Hapus Akun Pengguna: ${user.name}`}
                                                                url={route('administration.user.destroy', user.id)}
                                                                method="delete"
                                                                fields={[
                                                                    { name: 'reason', label: 'Alasan penghapusan akun', type: 'textarea' },
                                                                    { name: 'current_password', label: 'Konfirmasi password Anda', type: 'password' }
                                                                ]}
                                                                description="PERHATIAN: Akun ini akan dihapus permanen dari sistem. Email notifikasi resmi penghapusan akun akan otomatis dikirimkan ke pegawai yang bersangkutan."
                                                                buttonClassName="simon-button-danger !min-h-[28px] !py-1 !px-2 text-xs inline-flex items-center gap-1"
                                                                buttonContent={<><Trash2 className="h-3 w-3" /><span>Hapus</span></>}
                                                            />
                                                        </div>
                                                    ) : (
                                                        <span className="text-[11px] text-slate-400 italic">Akun Anda</span>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {/* MOBILE COMPACT CARDS VIEW (< md) */}
                    <div className="space-y-3 md:hidden">
                        {filteredUsers.map(user => {
                            const isSelf = user.id === auth.user.id;
                            const isExpanded = !!expandedUsers[user.id];
                            const initials = user.name
                                .split(' ')
                                .slice(0, 2)
                                .map(n => n[0])
                                .join('')
                                .toUpperCase();

                            const activeAssignments = user.role_assignments.filter(
                                a => !a.ends_at || new Date(a.ends_at) > new Date()
                            );

                            const userUnitName = user.profile?.unit?.name || (user.profile?.unit_id ? unitMap.get(user.profile.unit_id) : 'Belum ada unit');

                            return (
                                <div key={user.id} className="simon-card !p-3.5 space-y-3 bg-white border border-slate-200/80 shadow-xs">
                                    {/* Header: Avatar, Name, Email/NIP, Status Badge */}
                                    <div className="flex items-start justify-between gap-2.5">
                                        <div className="flex items-center gap-2.5 min-w-0">
                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100 font-bold text-teal-800 text-xs">
                                                {initials}
                                            </div>
                                            <div className="min-w-0">
                                                <div className="flex items-center gap-1.5 flex-wrap">
                                                    <h3 className="font-bold text-slate-900 text-sm truncate">
                                                        {user.name}
                                                    </h3>
                                                    {isSelf && (
                                                        <span className="rounded bg-slate-200 px-1.5 py-0.2 text-[9px] font-bold text-slate-700">
                                                            Anda
                                                        </span>
                                                    )}
                                                </div>
                                                <div className="flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-slate-500 truncate">
                                                    <span className="truncate">{user.email}</span>
                                                    {(() => {
                                                        const nip = user.profile?.nip?.trim();
                                                        if (!nip || nip.includes('@')) return null;
                                                        return (
                                                            <span className="shrink-0 text-[11px] font-mono text-slate-500 font-medium">
                                                                • NIP: {nip}
                                                            </span>
                                                        );
                                                    })()}
                                                </div>
                                            </div>
                                        </div>

                                        {/* Status Badge */}
                                        <div className="shrink-0">
                                            {user.status === 'active' && (
                                                <span className="simon-badge-success text-[10px] !py-0.5 !px-2 font-semibold">
                                                    Aktif
                                                </span>
                                            )}
                                            {user.status === 'pending' && (
                                                <span className="simon-badge-warning text-[10px] !py-0.5 !px-2 font-semibold animate-pulse">
                                                    Menunggu
                                                </span>
                                            )}
                                            {user.status === 'suspended' && (
                                                <span className="simon-badge-danger text-[10px] !py-0.5 !px-2 font-semibold">
                                                    Ditangguhkan
                                                </span>
                                            )}
                                        </div>
                                    </div>

                                    {/* Info Bar: Unit & Email Status */}
                                    <div className="flex flex-wrap items-center justify-between gap-2 rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs border border-slate-100">
                                        <div className="flex items-center gap-1.5 text-slate-700 font-medium truncate">
                                            <Building2 className="h-3.5 w-3.5 text-slate-400 shrink-0" />
                                            <span className="truncate text-[11px]">{userUnitName}</span>
                                        </div>

                                        {user.email_verified_at ? (
                                            <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-700">
                                                <CheckCircle2 className="h-3 w-3 text-emerald-500" />
                                                Email Valid
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">
                                                <AlertCircle className="h-3 w-3 text-amber-500" />
                                                Belum Verifikasi
                                            </span>
                                        )}
                                    </div>

                                    {/* Mandat & Penugasan Summary */}
                                    <div className="space-y-1.5">
                                        <div className="flex items-center justify-between text-[11px]">
                                            <span className="font-semibold text-slate-700 flex items-center gap-1">
                                                <Briefcase className="h-3 w-3 text-slate-400" />
                                                <span>Peran & Penugasan ({activeAssignments.length})</span>
                                            </span>
                                            {activeAssignments.length > 0 && (
                                                <button
                                                    type="button"
                                                    onClick={() => toggleExpand(user.id)}
                                                    className="text-[11px] font-medium text-teal-700 hover:text-teal-800 flex items-center gap-0.5"
                                                >
                                                    <span>{isExpanded ? 'Tutup Rincian' : 'Rincian / Edit'}</span>
                                                    {isExpanded ? <ChevronUp className="h-3 w-3" /> : <ChevronDown className="h-3 w-3" />}
                                                </button>
                                            )}
                                        </div>

                                        {/* Compact preview pills when collapsed */}
                                        {!isExpanded && (
                                            <div className="flex flex-wrap gap-1">
                                                {activeAssignments.map(a => (
                                                    <span
                                                        key={a.id}
                                                        className={`rounded px-2 py-0.5 text-[10px] font-semibold border ${
                                                            a.role.name === 'Koordinator'
                                                                ? 'bg-purple-50 text-purple-700 border-purple-200'
                                                                : a.role.name === 'Penanggung Jawab Ruangan'
                                                                ? 'bg-blue-50 text-blue-700 border-blue-200'
                                                                : 'bg-slate-100 text-slate-700 border-slate-200'
                                                        }`}
                                                    >
                                                        {a.role.name}
                                                    </span>
                                                ))}
                                                {!activeAssignments.length && (
                                                    <span className="text-[11px] text-slate-400 italic">Belum ada penugasan</span>
                                                )}
                                            </div>
                                        )}

                                        {/* Detailed expandable list with Edit / Revoke */}
                                        {isExpanded && activeAssignments.length > 0 && (
                                            <div className="space-y-2 rounded-lg bg-slate-50/80 p-2.5 border border-slate-200/60">
                                                {activeAssignments.map(assignment => {
                                                    const canManageAssignment =
                                                        !isSelf &&
                                                        (!assignment.ends_at || new Date(assignment.ends_at) > new Date()) &&
                                                        (isGlobal || (!assignment.is_global && !assignment.can_administer));

                                                    const aUnitName = assignment.unit_id ? (unitMap.get(assignment.unit_id) || `Unit #${assignment.unit_id}`) : 'Lintas Unit';
                                                    const aRoomName = assignment.room_id ? (roomMap.get(assignment.room_id) || `R.${assignment.room_id}`) : 'Semua Ruangan';

                                                    return (
                                                        <div key={assignment.id} className="flex items-center justify-between gap-2 border-b border-slate-200/50 pb-2 last:border-b-0 last:pb-0">
                                                            <div className="text-[11px] min-w-0">
                                                                <div className="font-semibold text-slate-800">{assignment.role.name}</div>
                                                                <div className="text-[10px] text-slate-500 truncate">
                                                                    {aUnitName} · {aRoomName}
                                                                </div>
                                                            </div>
                                                            {canManageAssignment && (
                                                                <div className="flex items-center gap-1 shrink-0">
                                                                    <ActionForm
                                                                        title="Edit Mandat Penugasan"
                                                                        url={route('administration.assignment.update', assignment.id)}
                                                                        fields={[
                                                                            {
                                                                                name: 'role',
                                                                                label: 'Peran / Mandat',
                                                                                type: 'select',
                                                                                required: true,
                                                                                options: ['Pegawai', 'Penanggung Jawab Ruangan', 'Koordinator'].map(value => ({ value, label: value }))
                                                                            },
                                                                            unitField,
                                                                            {
                                                                                name: 'room_id',
                                                                                label: 'Ruangan (kosong = cakupan unit)',
                                                                                type: 'select',
                                                                                required: false,
                                                                                options: rooms.map(r => ({ value: r.id, label: r.name }))
                                                                            },
                                                                            { name: 'ends_at', label: 'Batas akhir penugasan', type: 'date', required: false },
                                                                            { name: 'reason', label: 'Alasan penyesuaian penugasan', type: 'textarea' },
                                                                            { name: 'current_password', label: 'Password Anda (wajib saat peran Koordinator)', type: 'password', required: false }
                                                                        ]}
                                                                        initial={{
                                                                            role: assignment.role.name,
                                                                            unit_id: assignment.unit_id ?? '',
                                                                            room_id: assignment.room_id ?? '',
                                                                            ends_at: assignment.ends_at ? String(assignment.ends_at).substring(0, 10) : ''
                                                                        }}
                                                                        description="Perubahan mandat penugasan akan disimpan dan notifikasi email resmi akan dikirimkan ke pegawai."
                                                                        buttonClassName="simon-button-secondary !min-h-[24px] !py-0.5 !px-2 text-[10px]"
                                                                        label="Edit"
                                                                    />
                                                                    <ActionForm
                                                                        title="Cabut Penugasan"
                                                                        url={route('administration.assignment.revoke', assignment.id)}
                                                                        fields={[
                                                                            { name: 'reason', label: 'Alasan pencabutan / rencana pengganti', type: 'textarea' },
                                                                            { name: 'current_password', label: 'Password Anda', type: 'password' }
                                                                        ]}
                                                                        description="Akses mandat ini langsung berakhir dan sesi akun diakhiri. Riwayat dipertahankan."
                                                                        buttonClassName="simon-button-danger !min-h-[24px] !py-0.5 !px-2 text-[10px]"
                                                                        label="Cabut"
                                                                    />
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })}
                                            </div>
                                        )}
                                    </div>

                                    {/* Action Buttons Bottom Bar */}
                                    {!isSelf ? (
                                        <div className="flex items-center gap-2 pt-2 border-t border-slate-100">
                                            {user.status === 'pending' ? (
                                                <div className="flex-1">
                                                    {user.email_verified_at ? (
                                                        <ActionForm
                                                            title={`Aktivasi Akun Pegawai: ${user.name}`}
                                                            description="Aktifkan akun pegawai ini agar dapat segera login ke sistem SIMON BMN. Unit penugasan telah terdaftar sebagai Pegawai."
                                                            url={route('administration.user', user.id)}
                                                            fields={[
                                                                { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                                { name: 'reason', label: 'Catatan tambahan (opsional)', type: 'textarea', required: false, hint: 'Kosongkan jika tidak ada catatan khusus.' }
                                                            ]}
                                                            initial={{ status: 'active', reason: '' }}
                                                            buttonClassName="simon-button w-full !min-h-[34px] !py-1.5 text-xs font-semibold justify-center inline-flex items-center gap-1.5 shadow-xs"
                                                            buttonContent={<><UserCheck className="h-4 w-4" /><span>Terima Akun</span></>}
                                                        />
                                                    ) : (
                                                        <button
                                                            type="button"
                                                            disabled
                                                            className="simon-button-secondary w-full !min-h-[34px] !py-1.5 text-xs font-medium justify-center inline-flex items-center gap-1.5 opacity-60 cursor-not-allowed text-amber-800 bg-amber-50 border-amber-200"
                                                            title="Pegawai belum memverifikasi link di emailnya"
                                                        >
                                                            <Clock className="h-3.5 w-3.5 text-amber-600" />
                                                            <span>Menunggu Verifikasi Email</span>
                                                        </button>
                                                    )}
                                                </div>
                                            ) : user.status === 'active' ? (
                                                <div className="flex-1">
                                                    <ActionForm
                                                        title={`Tangguhkan Akun Pegawai: ${user.name}`}
                                                        description="Pengguna ini tidak dapat login ke sistem SIMON BMN selama statusnya ditangguhkan."
                                                        url={route('administration.user', user.id)}
                                                        fields={[
                                                            { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                            { name: 'reason', label: 'Alasan penangguhan akun', type: 'textarea', required: true, hint: 'Wajib diisi sebagai catatan pengawas.' }
                                                        ]}
                                                        initial={{ status: 'suspended', reason: '' }}
                                                        buttonClassName="simon-button-secondary w-full !min-h-[34px] !py-1.5 text-xs font-medium justify-center text-amber-700 hover:text-amber-800 border-amber-300 hover:bg-amber-50 inline-flex items-center gap-1.5"
                                                        buttonContent={<><ShieldAlert className="h-3.5 w-3.5" /><span>Tangguhkan</span></>}
                                                    />
                                                </div>
                                            ) : (
                                                <div className="flex-1">
                                                    <ActionForm
                                                        title={`Pulihkan Akun Pegawai: ${user.name}`}
                                                        description="Status akun akan dipulihkan menjadi aktif sehingga pegawai dapat kembali login ke sistem."
                                                        url={route('administration.user', user.id)}
                                                        fields={[
                                                            { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                            { name: 'reason', label: 'Catatan pemulihan (opsional)', type: 'textarea', required: false }
                                                        ]}
                                                        initial={{ status: 'active', reason: '' }}
                                                        buttonClassName="simon-button-secondary w-full !min-h-[34px] !py-1.5 text-xs font-medium justify-center text-emerald-700 hover:text-emerald-800 border-emerald-300 hover:bg-emerald-50 inline-flex items-center gap-1.5"
                                                        buttonContent={<><CheckCircle2 className="h-3.5 w-3.5" /><span>Pulihkan Akun</span></>}
                                                    />
                                                </div>
                                            )}

                                            <ActionForm
                                                title={`Edit Data Pegawai: ${user.name}`}
                                                description="Perbarui informasi data nama lengkap, NIP resmi, atau nomor kontak dinas pegawai."
                                                url={route('administration.user', user.id)}
                                                fields={[
                                                    { name: 'status', label: 'Status Akun', type: 'hidden' },
                                                    { name: 'name', label: 'Nama Lengkap & Gelar', type: 'text', required: true },
                                                    { name: 'nip', label: 'NIP (kosongkan jika tidak ada)', type: 'text', required: false, hint: '18 digit angka NIP resmi.' },
                                                    { name: 'phone', label: 'Nomor WhatsApp / HP', type: 'text', required: false },
                                                    unitField,
                                                    { name: 'reason', label: 'Catatan (opsional)', type: 'textarea', required: false }
                                                ]}
                                                initial={{
                                                    status: user.status,
                                                    name: user.name,
                                                    nip: user.profile?.nip ?? '',
                                                    phone: user.profile?.phone ?? '',
                                                    unit_id: user.profile?.unit_id ?? '',
                                                    reason: ''
                                                }}
                                                buttonClassName="simon-button-secondary !min-h-[34px] !py-1.5 !px-3 text-xs font-medium text-slate-700 hover:text-slate-900 border-slate-300 inline-flex items-center gap-1 shrink-0"
                                                buttonContent={<><Pencil className="h-3.5 w-3.5" /><span>Edit</span></>}
                                            />

                                            <ActionForm
                                                title={`Hapus Akun Pengguna: ${user.name}`}
                                                url={route('administration.user.destroy', user.id)}
                                                method="delete"
                                                fields={[
                                                    { name: 'reason', label: 'Alasan penghapusan akun', type: 'textarea' },
                                                    { name: 'current_password', label: 'Konfirmasi password Anda', type: 'password' }
                                                ]}
                                                description="PERHATIAN: Akun ini akan dihapus permanen dari sistem. Email notifikasi resmi penghapusan akun akan otomatis dikirimkan ke pegawai."
                                                buttonClassName="simon-button-danger !min-h-[34px] !py-1.5 !px-3 text-xs font-semibold inline-flex items-center gap-1 shrink-0"
                                                buttonContent={<><Trash2 className="h-3.5 w-3.5" /><span>Hapus</span></>}
                                            />
                                        </div>
                                    ) : (
                                        <div className="text-center text-[11px] text-slate-400 italic py-1 border-t border-slate-100">
                                            Akun Anda Sendiri
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>

                    {/* Empty State */}
                    {!filteredUsers.length && (
                        <div className="simon-card text-center py-10">
                            <Users className="mx-auto h-8 w-8 text-slate-300 mb-2" />
                            <p className="font-semibold text-slate-700 text-sm">Tidak ada pengguna yang cocok</p>
                            <p className="text-xs text-slate-500 mt-1">Coba ubah filter status atau kata kunci pencarian Anda.</p>
                        </div>
                    )}

                    {/* Pagination */}
                    <div className="pt-2">
                        <Pagination data={users} />
                    </div>
                </div>
            )}

            {/* TAB 2: ORGANISASI & MASTER DATA */}
            {activeTab === 'organization' && (
                <div className="space-y-6">
                    {/* Organization Sub-tabs for quick navigation */}
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <div className="flex flex-wrap items-center gap-1.5">
                            <button
                                type="button"
                                onClick={() => setOrgSubTab('all')}
                                className={`rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    orgSubTab === 'all'
                                        ? 'bg-slate-900 text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                Semua Master Data
                            </button>
                            <button
                                type="button"
                                onClick={() => setOrgSubTab('units')}
                                className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    orgSubTab === 'units'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                <Building2 className="h-3.5 w-3.5" />
                                Unit Kerja ({units.length})
                            </button>
                            <button
                                type="button"
                                onClick={() => setOrgSubTab('rooms')}
                                className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    orgSubTab === 'rooms'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                <DoorClosed className="h-3.5 w-3.5" />
                                Ruangan ({rooms.length})
                            </button>
                            <button
                                type="button"
                                onClick={() => setOrgSubTab('categories')}
                                className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all ${
                                    orgSubTab === 'categories'
                                        ? 'bg-primary text-white shadow-xs'
                                        : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                }`}
                            >
                                <Layers className="h-3.5 w-3.5" />
                                Kategori Aset ({categories.length})
                            </button>
                        </div>

                        {/* Fast Placement Link */}
                        {isGlobal && (
                            <Link 
                                href={route('placement.index')}
                                className="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                            >
                                <span>Penempatan Aset Tanpa Ruangan</span>
                                <ArrowRight className="h-3 w-3" />
                            </Link>
                        )}
                    </div>

                    {/* Master Data Grid */}
                    <div className="grid gap-5 md:grid-cols-3">
                        {/* Section 1: Unit Kerja */}
                        {(orgSubTab === 'all' || orgSubTab === 'units') && (
                            <section className="simon-card !p-5 flex flex-col justify-between space-y-4">
                                <div>
                                    <div className="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-teal-50 text-teal-700 border border-teal-200/60">
                                                <Building2 className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h2 className="font-bold text-slate-900 text-sm">Unit Kerja</h2>
                                                <p className="text-[11px] text-slate-500">{units.length} unit terdaftar</p>
                                            </div>
                                        </div>

                                        {isGlobal && (
                                            <ActionForm 
                                                title="Tambah Unit Kerja" 
                                                url={route('administration.organization', 'unit')} 
                                                fields={[
                                                    { name: 'name', label: 'Nama Unit Kerja' }, 
                                                    { name: 'code', label: 'Kode Unit' }
                                                ]}
                                                buttonClassName="simon-button !min-h-[28px] !py-1 !px-2.5 text-xs inline-flex items-center gap-1"
                                                buttonContent={<><Plus className="h-3 w-3" /><span>Tambah</span></>}
                                            />
                                        )}
                                    </div>

                                    {/* Scrollable list of Units */}
                                    <div className="mt-3 max-h-72 overflow-y-auto pr-1 space-y-2">
                                        {units.map(unit => (
                                            <div key={unit.id} className="flex items-center justify-between gap-2 rounded-lg bg-slate-50/80 p-2 text-xs border border-slate-100 hover:border-slate-200 transition-colors">
                                                <span className="font-medium text-slate-800 truncate">{unit.name}</span>
                                                <span className="font-mono text-[10px] font-bold text-slate-500 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                                    {unit.code || `#${unit.id}`}
                                                </span>
                                            </div>
                                        ))}
                                        {!units.length && (
                                            <p className="text-xs text-slate-400 py-4 text-center">Belum ada unit kerja.</p>
                                        )}
                                    </div>
                                </div>
                            </section>
                        )}

                        {/* Section 2: Ruangan Kerja */}
                        {(orgSubTab === 'all' || orgSubTab === 'rooms') && (
                            <section className="simon-card !p-5 flex flex-col justify-between space-y-4">
                                <div>
                                    <div className="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-amber-50 text-amber-700 border border-amber-200/60">
                                                <DoorClosed className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h2 className="font-bold text-slate-900 text-sm">Ruangan Kerja</h2>
                                                <p className="text-[11px] text-slate-500">{rooms.length} ruangan tercatat</p>
                                            </div>
                                        </div>

                                        <ActionForm 
                                            title="Tambah Ruangan" 
                                            url={route('administration.organization', 'room')} 
                                            fields={[
                                                { name: 'name', label: 'Nama Ruangan' }, 
                                                { name: 'code', label: 'Kode Ruangan' },
                                                { ...unitField, required: true }
                                            ]}
                                            buttonClassName="simon-button !min-h-[28px] !py-1 !px-2.5 text-xs inline-flex items-center gap-1"
                                            buttonContent={<><Plus className="h-3 w-3" /><span>Tambah</span></>}
                                        />
                                    </div>

                                    {/* Scrollable list of Rooms */}
                                    <div className="mt-3 max-h-72 overflow-y-auto pr-1 space-y-2">
                                        {rooms.map(room => {
                                            const roomUnitName = unitMap.get(room.unit_id);
                                            return (
                                                <div key={room.id} className="rounded-lg bg-slate-50/80 p-2 text-xs border border-slate-100 hover:border-slate-200 transition-colors space-y-1">
                                                    <div className="flex items-center justify-between gap-2">
                                                        <span className="font-medium text-slate-800 truncate">{room.name}</span>
                                                        <span className="font-mono text-[10px] font-bold text-slate-500 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                                            {room.code || `#${room.id}`}
                                                        </span>
                                                    </div>
                                                    {roomUnitName && (
                                                        <div className="text-[10px] text-slate-500 flex items-center gap-1">
                                                            <Building2 className="h-2.5 w-2.5 text-slate-400" />
                                                            <span className="truncate">{roomUnitName}</span>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                        {!rooms.length && (
                                            <p className="text-xs text-slate-400 py-4 text-center">Belum ada ruangan kerja.</p>
                                        )}
                                    </div>
                                </div>
                            </section>
                        )}

                        {/* Section 3: Kategori Aset */}
                        {(orgSubTab === 'all' || orgSubTab === 'categories') && (
                            <section className="simon-card !p-5 flex flex-col justify-between space-y-4">
                                <div>
                                    <div className="flex items-center justify-between gap-2 border-b border-slate-100 pb-3">
                                        <div className="flex items-center gap-2">
                                            <div className="flex h-7 w-7 items-center justify-center rounded-lg bg-purple-50 text-purple-700 border border-purple-200/60">
                                                <Layers className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <h2 className="font-bold text-slate-900 text-sm">Kategori Aset</h2>
                                                <p className="text-[11px] text-slate-500">{categories.length} kategori barang</p>
                                            </div>
                                        </div>

                                        {isGlobal && (
                                            <ActionForm 
                                                title="Tambah Kategori Aset" 
                                                url={route('administration.organization', 'category')} 
                                                fields={[
                                                    { name: 'name', label: 'Nama Kategori' }, 
                                                    { name: 'code', label: 'Kode Kategori' }
                                                ]}
                                                buttonClassName="simon-button !min-h-[28px] !py-1 !px-2.5 text-xs inline-flex items-center gap-1"
                                                buttonContent={<><Plus className="h-3 w-3" /><span>Tambah</span></>}
                                            />
                                        )}
                                    </div>

                                    {/* Scrollable list of Categories */}
                                    <div className="mt-3 max-h-72 overflow-y-auto pr-1 space-y-2">
                                        {categories.map(category => (
                                            <div key={category.id} className="flex items-center justify-between gap-2 rounded-lg bg-slate-50/80 p-2 text-xs border border-slate-100 hover:border-slate-200 transition-colors">
                                                <span className="font-medium text-slate-800 truncate">{category.name}</span>
                                                <span className="font-mono text-[10px] font-bold text-slate-500 bg-white px-1.5 py-0.5 rounded border border-slate-200">
                                                    {category.code || `#${category.id}`}
                                                </span>
                                            </div>
                                        ))}
                                        {!categories.length && (
                                            <p className="text-xs text-slate-400 py-4 text-center">Belum ada kategori aset.</p>
                                        )}
                                    </div>
                                </div>
                            </section>
                        )}
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
