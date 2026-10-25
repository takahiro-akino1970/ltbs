<script setup lang="ts">
import OrderLayout from '../../../layouts/order/OrderLayout.vue';
import type { OrderBreadcrumb } from '../../../types/order';

const breadcrumbs: OrderBreadcrumb[] = [{ label: '発注履歴' }];

const columns = ['発注番号', '引取日', '引取地', '納車地', '車両', 'ステータス'];
</script>

<template>
    <OrderLayout
        title="発注履歴"
        lead="過去の発注を検索・絞り込みして確認します。"
        :breadcrumbs="breadcrumbs"
    >
        <template #actions>
            <a
                href="/orders/create"
                class="btn btn-primary btn-sm"
            >
                新規発注
            </a>
        </template>

        <div class="card mb-3">
            <div class="card-body text-body-secondary small">
                検索・絞り込み（ステータス、期間、地点、車両番号）は未実装です。
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column"
                                scope="col"
                                class="small text-nowrap"
                            >
                                {{ column }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td
                                :colspan="columns.length"
                                class="text-center text-body-secondary py-4"
                            >
                                発注データはまだありません。
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </OrderLayout>
</template>
