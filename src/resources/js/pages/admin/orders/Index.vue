<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { api, getApiErrorMessage } from '../../../api';
import AdminLayout from '../../../layouts/admin/AdminLayout.vue';
import type { AdminBreadcrumb, CustomerOption } from '../../../types/admin';

const breadcrumbs: AdminBreadcrumb[] = [{ label: '発注管理' }];
const customerOptions = ref<CustomerOption[]>([]);
const isLoadingCustomerOptions = ref(true);
const customerOptionsError = ref('');

async function loadCustomerOptions(): Promise<void> {
    isLoadingCustomerOptions.value = true;
    customerOptionsError.value = '';

    try {
        const response = await api.get<{ data: CustomerOption[] }>('/admin/customer-options');
        customerOptions.value = response.data.data;
    } catch (error: unknown) {
        customerOptionsError.value = getApiErrorMessage(
            error,
            '得意先候補を読み込めませんでした。',
        );
    } finally {
        isLoadingCustomerOptions.value = false;
    }
}

onMounted(() => {
    void loadCustomerOptions();
});

const orders = [
    {
        number: 'ORD-2026-001',
        customer: 'サンプル運輸株式会社',
        vehicle: '品川 300 あ 12-34',
        pickup: '2026/10/16 09:00',
        delivery: '2026/10/17 15:00',
        status: '新規発注',
        tone: 'text-bg-danger',
    },
    {
        number: 'ORD-2026-002',
        customer: 'テスト商事株式会社',
        vehicle: '横浜 500 い 56-78',
        pickup: '2026/10/16 13:00',
        delivery: '2026/10/18 10:00',
        status: '受注済み',
        tone: 'text-bg-primary',
    },
];
</script>

<template>
    <AdminLayout
        title="発注管理"
        lead="複数の得意先から届いた発注を横断して確認します。"
        :breadcrumbs="breadcrumbs"
    >
        <div
            v-if="customerOptionsError"
            class="alert alert-danger d-flex align-items-center justify-content-between gap-3"
            role="alert"
        >
            <span>{{ customerOptionsError }}</span>
            <button
                type="button"
                class="btn btn-outline-danger btn-sm text-nowrap"
                @click="loadCustomerOptions"
            >
                再試行
            </button>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-12 col-lg-4">
                        <label
                            for="admin-order-keyword"
                            class="form-label small"
                        >フリーワード</label>
                        <input
                            id="admin-order-keyword"
                            type="search"
                            class="form-control form-control-sm"
                            placeholder="発注番号・車両番号・得意先"
                            disabled
                        >
                    </div>
                    <div class="col-6 col-lg-3">
                        <label
                            for="admin-order-status"
                            class="form-label small"
                        >ステータス</label>
                        <select
                            id="admin-order-status"
                            class="form-select form-select-sm"
                            disabled
                        >
                            <option>すべて</option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-3">
                        <label
                            for="admin-order-customer"
                            class="form-label small"
                        >得意先</label>
                        <select
                            id="admin-order-customer"
                            class="form-select form-select-sm"
                            :disabled="isLoadingCustomerOptions || customerOptionsError !== ''"
                        >
                            <option>すべて</option>
                            <option
                                v-for="customer in customerOptions"
                                :key="customer.id"
                                :value="customer.id"
                            >
                                {{ customer.customer_name }}
                                <template v-if="customer.customer_code">
                                    （{{ customer.customer_code }}）
                                </template>
                            </option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-2 d-flex align-items-end">
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm w-100"
                            disabled
                        >
                            検索
                        </button>
                    </div>
                </div>
                <p class="text-body-secondary small mt-2 mb-0">
                    検索・絞り込みは未実装です。
                </p>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col">
                                発注番号
                            </th>
                            <th scope="col">
                                得意先
                            </th>
                            <th scope="col">
                                車両番号
                            </th>
                            <th scope="col">
                                引取予定
                            </th>
                            <th scope="col">
                                納車予定
                            </th>
                            <th scope="col">
                                ステータス
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders"
                            :key="order.number"
                        >
                            <td>
                                <a :href="`/orders/${order.number}`">
                                    {{ order.number }}
                                </a>
                            </td>
                            <td class="text-nowrap">
                                {{ order.customer }}
                            </td>
                            <td class="text-nowrap">
                                {{ order.vehicle }}
                            </td>
                            <td class="text-nowrap">
                                {{ order.pickup }}
                            </td>
                            <td class="text-nowrap">
                                {{ order.delivery }}
                            </td>
                            <td>
                                <span
                                    class="badge text-nowrap"
                                    :class="order.tone"
                                >{{ order.status }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-body-secondary small">
                表示確認用のサンプルデータです。
            </div>
        </div>
    </AdminLayout>
</template>
