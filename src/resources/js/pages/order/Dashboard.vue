<script setup lang="ts">
import OrderLayout from '../../layouts/order/OrderLayout.vue';

const statusSummary = [
    { label: '下書き', count: 0 },
    { label: '発注済み', count: 0 },
    { label: '受付済み', count: 0 },
    { label: '輸送中', count: 0 },
    { label: '完了', count: 0 },
];
</script>

<template>
    <OrderLayout
        title="ダッシュボード"
        lead="発注状況の概要と最近の発注を確認します。"
    >
        <template #actions>
            <a
                href="/orders/create"
                class="btn btn-primary btn-sm"
            >
                新規発注
            </a>
            <a
                href="/orders"
                class="btn btn-outline-secondary btn-sm"
            >
                発注履歴
            </a>
        </template>

        <div class="row g-3 mb-3">
            <div
                v-for="status in statusSummary"
                :key="status.label"
                class="col-6 col-md-4 col-xl"
            >
                <div class="card h-100">
                    <div class="card-body">
                        <p class="text-body-secondary small mb-1">
                            {{ status.label }}
                        </p>
                        <p class="h4 mb-0">
                            {{ status.count }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                最近の発注
            </div>
            <div class="card-body text-body-secondary">
                発注データはまだありません。
            </div>
        </div>
    </OrderLayout>
</template>
