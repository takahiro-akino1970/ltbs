<script setup lang="ts">
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import OrderLayout from '../../../layouts/order/OrderLayout.vue';
import type { OrderBreadcrumb } from '../../../types/order';

const props = defineProps<{
    orderNumber: string;
}>();

const breadcrumbs: OrderBreadcrumb[] = [
    { label: '発注履歴', href: '/orders' },
    { label: props.orderNumber },
];

const tabs = ['概要', '車両', '地点', '連絡事項', '履歴'];
const activeTab = ref(tabs[0]);
</script>

<template>
    <OrderLayout
        :title="`発注詳細 ${orderNumber}`"
        lead="発注内容と進捗を確認します。"
        :breadcrumbs="breadcrumbs"
    >
        <template #actions>
            <Link
                :href="`/orders/${orderNumber}/reorder`"
                class="btn btn-outline-primary btn-sm"
            >
                再発注
            </Link>
        </template>

        <div class="card">
            <div class="card-header pb-0">
                <ul class="nav nav-tabs card-header-tabs">
                    <li
                        v-for="tab in tabs"
                        :key="tab"
                        class="nav-item"
                    >
                        <button
                            type="button"
                            class="nav-link"
                            :class="{ active: tab === activeTab }"
                            @click="activeTab = tab"
                        >
                            {{ tab }}
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body text-body-secondary">
                「{{ activeTab }}」の内容は未実装です。
            </div>
        </div>
    </OrderLayout>
</template>
