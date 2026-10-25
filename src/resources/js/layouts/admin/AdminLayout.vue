<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Offcanvas } from 'bootstrap';
import AdminSideNav from '../../components/admin/AdminSideNav.vue';
import { useAppConfig } from '../../appConfig';
import type { AdminBreadcrumb, AdminNavItem } from '../../types/admin';

const props = withDefaults(
    defineProps<{
        title: string;
        lead?: string;
        breadcrumbs?: AdminBreadcrumb[];
    }>(),
    {
        lead: undefined,
        breadcrumbs: () => [],
    },
);

const { appName } = useAppConfig();
const currentPath = window.location.pathname;

const sideNavItems: AdminNavItem[] = [
    { label: 'ダッシュボード', href: '/', available: true },
    { label: '発注管理', href: '/orders', available: true },
    { label: '輸送進捗', href: '/operations', available: true },
    { label: '通知・連絡', href: '/notifications', available: false },
    { label: '得意先管理', href: '/customers', available: true },
    { label: 'ユーザー管理', href: '/users', available: false },
    { label: 'ロール管理', href: '/roles', available: false },
    { label: '地点管理', href: '/locations', available: false },
    { label: 'マスタ管理', href: '/masters', available: false },
    { label: '監査ログ', href: '/audit-logs', available: false },
    { label: 'システム設定', href: '/settings', available: false },
];

const bottomNavItems: AdminNavItem[] = [
    { label: 'ホーム', href: '/', available: true },
    { label: '発注管理', href: '/orders', available: true },
    { label: '輸送進捗', href: '/operations', available: true },
    { label: '通知', href: '/notifications', available: false },
];

function resolveActiveHref(items: AdminNavItem[], path: string): string {
    return (
        items
            .filter((item) =>
                item.href === '/'
                    ? path === '/'
                    : path === item.href || path.startsWith(`${item.href}/`),
            )
            .sort((a, b) => b.href.length - a.href.length)[0]?.href ?? ''
    );
}

const activeSideHref = computed(() => resolveActiveHref(sideNavItems, currentPath));
const activeBottomHref = computed(() => resolveActiveHref(bottomNavItems, currentPath));

const sideNavElement = ref<HTMLElement | null>(null);
let sideNavOffcanvas: Offcanvas | null = null;

onMounted(() => {
    document.title = `${props.title} | ${appName}`;

    if (sideNavElement.value === null) {
        return;
    }

    sideNavOffcanvas = Offcanvas.getOrCreateInstance(sideNavElement.value);
});

onBeforeUnmount(() => {
    sideNavOffcanvas?.dispose();
    sideNavOffcanvas = null;
});
</script>

<template>
    <div class="admin-shell min-vh-100 d-flex flex-column bg-body-tertiary">
        <header class="admin-header border-bottom bg-body">
            <div class="container-fluid d-flex align-items-center gap-2 gap-md-3 py-2">
                <button
                    class="btn btn-sm btn-outline-secondary d-lg-none"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#adminSideNav"
                    aria-controls="adminSideNav"
                >
                    メニュー
                </button>

                <a
                    href="/"
                    class="fw-bold text-body text-decoration-none text-nowrap"
                >
                    {{ appName }}
                </a>

                <div class="d-none d-md-block flex-grow-1">
                    <input
                        type="search"
                        class="form-control form-control-sm"
                        placeholder="発注番号・車両番号・得意先で検索"
                        aria-label="管理データ検索"
                        disabled
                    >
                </div>

                <div class="ms-auto d-flex align-items-center gap-2">
                    <span
                        class="btn btn-sm btn-outline-secondary disabled"
                        aria-disabled="true"
                    >通知</span>
                    <span
                        class="btn btn-sm btn-outline-secondary disabled d-none d-sm-inline-block"
                        aria-disabled="true"
                    >ヘルプ</span>
                    <span
                        class="btn btn-sm btn-outline-secondary disabled"
                        aria-disabled="true"
                    >管理者</span>
                </div>
            </div>
        </header>

        <div class="container-fluid flex-grow-1 d-flex flex-column">
            <div class="row g-0 flex-grow-1">
                <aside class="col-lg-3 col-xl-2 d-none d-lg-block border-end bg-body py-3 pe-3">
                    <AdminSideNav
                        :items="sideNavItems"
                        :active-href="activeSideHref"
                    />
                </aside>

                <main class="col-lg-9 col-xl-10 py-3 px-lg-4">
                    <nav
                        v-if="props.breadcrumbs.length > 0"
                        aria-label="パンくずリスト"
                    >
                        <ol class="breadcrumb small">
                            <li class="breadcrumb-item">
                                <a href="/">
                                    ダッシュボード
                                </a>
                            </li>
                            <li
                                v-for="(crumb, index) in props.breadcrumbs"
                                :key="crumb.label"
                                class="breadcrumb-item"
                                :class="{ active: index === props.breadcrumbs.length - 1 }"
                                :aria-current="index === props.breadcrumbs.length - 1 ? 'page' : undefined"
                            >
                                <a
                                    v-if="crumb.href && index !== props.breadcrumbs.length - 1"
                                    :href="crumb.href"
                                >
                                    {{ crumb.label }}
                                </a>
                                <span v-else>{{ crumb.label }}</span>
                            </li>
                        </ol>
                    </nav>

                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                        <div>
                            <h1 class="h4 mb-1">
                                {{ props.title }}
                            </h1>
                            <p
                                v-if="props.lead"
                                class="text-body-secondary small mb-0"
                            >
                                {{ props.lead }}
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <slot name="actions" />
                        </div>
                    </div>

                    <slot />
                </main>
            </div>
        </div>

        <nav
            class="admin-bottom-nav d-lg-none border-top bg-body"
            aria-label="主要メニュー"
        >
            <ul class="nav nav-justified">
                <li
                    v-for="item in bottomNavItems"
                    :key="item.href"
                    class="nav-item"
                >
                    <a
                        v-if="item.available"
                        :href="item.href"
                        class="nav-link small py-2"
                        :class="{ active: item.href === activeBottomHref }"
                        :aria-current="item.href === activeBottomHref ? 'page' : undefined"
                    >
                        {{ item.label }}
                    </a>
                    <span
                        v-else
                        class="nav-link small py-2 disabled"
                        aria-disabled="true"
                    >
                        {{ item.label }}
                    </span>
                </li>
            </ul>
        </nav>

        <div
            id="adminSideNav"
            ref="sideNavElement"
            class="offcanvas offcanvas-start"
            tabindex="-1"
            aria-labelledby="adminSideNavLabel"
        >
            <div class="offcanvas-header border-bottom">
                <h2
                    id="adminSideNavLabel"
                    class="offcanvas-title h6 mb-0"
                >
                    管理メニュー
                </h2>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="offcanvas"
                    aria-label="閉じる"
                />
            </div>
            <div class="offcanvas-body">
                <AdminSideNav
                    :items="sideNavItems"
                    :active-href="activeSideHref"
                />
            </div>
        </div>
    </div>
</template>

<style scoped lang="scss">
.admin-header {
    position: sticky;
    top: 0;
    z-index: 1020;
}

.admin-bottom-nav {
    position: sticky;
    bottom: 0;
    z-index: 1020;

    .nav-link.active {
        background-color: var(--bs-secondary-bg);
        font-weight: 600;
    }
}
</style>
