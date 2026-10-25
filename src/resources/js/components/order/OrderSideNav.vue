<script setup lang="ts">
import type { OrderNavItem } from '../../types/order';

defineProps<{
    items: OrderNavItem[];
    activeHref: string;
}>();
</script>

<template>
    <nav
        class="nav flex-column gap-1"
        aria-label="メインメニュー"
    >
        <template
            v-for="item in items"
            :key="item.href"
        >
            <a
                v-if="item.available"
                :href="item.href"
                class="nav-link rounded px-3 py-2"
                :class="{ active: item.href === activeHref }"
                :aria-current="item.href === activeHref ? 'page' : undefined"
            >
                {{ item.label }}
            </a>
            <span
                v-else
                class="nav-link disabled rounded px-3 py-2 d-flex align-items-center justify-content-between gap-2"
                aria-disabled="true"
            >
                {{ item.label }}
                <span class="badge text-bg-secondary fw-normal">未実装</span>
            </span>
        </template>
    </nav>
</template>

<style scoped lang="scss">
.nav-link.active {
    background-color: var(--bs-secondary-bg);
    font-weight: 600;
}
</style>
