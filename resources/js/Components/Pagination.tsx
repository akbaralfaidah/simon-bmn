import { Link } from '@inertiajs/react';
export type Paginated<T> = { data: T[]; current_page: number; last_page: number; total: number; prev_page_url?: string | null; next_page_url?: string | null };
export default function Pagination({ data }: { data: Paginated<unknown> }) {
    return <nav aria-label="Navigasi halaman" className="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm">
        <span>{data.total} data · Halaman {data.current_page} dari {data.last_page}</span>
        <div className="flex gap-2">{data.prev_page_url && <Link className="simon-button-secondary" href={data.prev_page_url}>Sebelumnya</Link>}{data.next_page_url && <Link className="simon-button-secondary" href={data.next_page_url}>Berikutnya</Link>}</div>
    </nav>;
}
