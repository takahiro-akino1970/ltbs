<script setup lang="ts">
import { computed, reactive } from 'vue';
import { InfiniteScroll, router } from '@inertiajs/vue3';
import AdminLayout from '../../../layouts/admin/AdminLayout.vue';
import type {
    AdminBreadcrumb,
    CursorPaginated,
    CustomerFilters,
    CustomerListItem,
} from '../../../types/admin';

const props = defineProps<{
    customers: CursorPaginated<CustomerListItem>;
    filters: CustomerFilters;
}>();

const breadcrumbs: AdminBreadcrumb[] = [{ label: '得意先管理' }];
const columnCount = 7;

const form = reactive<CustomerFilters>({
    keyword: props.filters.keyword,
    status: props.filters.status,
});

const hasRows = computed(() => props.customers.data.length > 0);
const hasMore = computed(() => props.customers.next_cursor !== null);

function search(): void {
    router.get(
        '/customers',
        { keyword: form.keyword, status: form.status },
        {
            only: ['customers', 'filters'],
            reset: ['customers'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

function resetFilters(): void {
    form.keyword = '';
    form.status = 'active';
    search();
}
</script>

<template>
    <AdminLayout
        title="得意先管理"
        lead="基幹システムに登録されている得意先を参照します。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="card mb-3">
            <div class="card-body">
                <form
                    class="row g-2"
                    @submit.prevent="search"
                >
                    <div class="col-12 col-lg-5">
                        <label
                            for="admin-customer-keyword"
                            class="form-label small"
                        >フリーワード</label>
                        <input
                            id="admin-customer-keyword"
                            v-model="form.keyword"
                            type="search"
                            class="form-control form-control-sm"
                            placeholder="得意先コード・得意先名・カナ・電話番号"
                        >
                    </div>
                    <div class="col-6 col-lg-3">
                        <label
                            for="admin-customer-status"
                            class="form-label small"
                        >状態</label>
                        <select
                            id="admin-customer-status"
                            v-model="form.status"
                            class="form-select form-select-sm"
                        >
                            <option value="active">
                                有効のみ
                            </option>
                            <option value="inactive">
                                無効のみ
                            </option>
                            <option value="all">
                                すべて
                            </option>
                        </select>
                    </div>
                    <div class="col-6 col-lg-2 d-flex align-items-end">
                        <button
                            type="submit"
                            class="btn btn-outline-primary btn-sm w-100"
                        >
                            検索
                        </button>
                    </div>
                    <div class="col-12 col-lg-2 d-flex align-items-end">
                        <button
                            type="button"
                            class="btn btn-outline-secondary btn-sm w-100"
                            @click="resetFilters"
                        >
                            条件クリア
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div id="admin-customer-scroll-start" />
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th scope="col">
                                    得意先コード
                                </th>
                                <th scope="col">
                                    得意先名
                                </th>
                                <th scope="col">
                                    カナ
                                </th>
                                <th scope="col">
                                    都道府県
                                </th>
                                <th scope="col">
                                    住所
                                </th>
                                <th scope="col">
                                    電話番号
                                </th>
                                <th scope="col">
                                    担当者
                                </th>
                            </tr>
                        </thead>
                        <InfiniteScroll
                            v-slot="{ loadingNext }"
                            data="customers"
                            as="tbody"
                            only-next
                            start-element="#admin-customer-scroll-start"
                            end-element="#admin-customer-scroll-end"
                        >
                            <tr
                                v-for="customer in customers.data"
                                :key="customer.id"
                                :class="{ 'text-body-secondary': !customer.avail_flg }"
                            >
                                <td class="text-nowrap">
                                    {{ customer.customer_code ?? '—' }}
                                </td>
                                <td>
                                    {{ customer.customer_name }}
                                    <span
                                        v-if="!customer.avail_flg"
                                        class="badge text-bg-secondary ms-1"
                                    >無効</span>
                                </td>
                                <td>{{ customer.customer_name_kn ?? '—' }}</td>
                                <td class="text-nowrap">
                                    {{ customer.pref ?? '—' }}
                                </td>
                                <td>{{ customer.address1 ?? '—' }}</td>
                                <td class="text-nowrap">
                                    {{ customer.tel ?? '—' }}
                                </td>
                                <td class="text-nowrap">
                                    {{ customer.charge_name ?? '—' }}
                                </td>
                            </tr>
                            <tr
                                v-if="!hasRows"
                                data-infinite-scroll-ignore
                            >
                                <td
                                    :colspan="columnCount"
                                    class="text-center text-body-secondary py-4"
                                >
                                    条件に一致する得意先はありません。
                                </td>
                            </tr>
                            <tr
                                v-else-if="loadingNext"
                                data-infinite-scroll-ignore
                            >
                                <td
                                    :colspan="columnCount"
                                    class="text-center text-body-secondary py-3"
                                >
                                    <span
                                        class="spinner-border spinner-border-sm me-2"
                                        aria-hidden="true"
                                    />読み込み中…
                                </td>
                            </tr>
                        </InfiniteScroll>
                    </table>
                </div>
            </div>
            <div id="admin-customer-scroll-end" />
            <div
                class="card-footer text-body-secondary small"
                aria-live="polite"
            >
                <template v-if="!hasRows">
                    0件
                </template>
                <template v-else-if="hasMore">
                    スクロールすると続きを読み込みます。
                </template>
                <template v-else>
                    すべての得意先を表示しました。
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
