export interface OrderNavItem {
    label: string;
    href: string;
    /** 第1段階で未実装の導線は false にして、押せないことを明示する。 */
    available: boolean;
}

export interface OrderBreadcrumb {
    label: string;
    href?: string;
}
