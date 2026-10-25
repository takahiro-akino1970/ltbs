export interface AdminNavItem {
    label: string;
    href: string;
    available: boolean;
}

export interface AdminBreadcrumb {
    label: string;
    href?: string;
}

export interface CustomerOption {
    id: number;
    customer_code: string | null;
    customer_name: string;
}

export interface CustomerListItem {
    id: number;
    customer_code: string | null;
    customer_name: string;
    customer_name_kn: string | null;
    pref: string | null;
    address1: string | null;
    tel: string | null;
    charge_name: string | null;
    avail_flg: boolean;
}

export interface CustomerFilters {
    keyword: string;
    status: 'active' | 'inactive' | 'all';
}

export interface CursorPaginated<T> {
    data: T[];
    path: string;
    per_page: number;
    next_cursor: string | null;
    next_page_url: string | null;
    prev_cursor: string | null;
    prev_page_url: string | null;
}
