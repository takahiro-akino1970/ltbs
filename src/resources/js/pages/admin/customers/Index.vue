<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { api, getApiErrorMessage } from '../../../api';
import AdminLayout from '../../../layouts/admin/AdminLayout.vue';
import type {
    AdminBreadcrumb,
    CursorPaginated,
    CustomerFilters,
    CustomerListItem,
} from '../../../types/admin';

const props = defineProps<{
    filters: CustomerFilters;
}>();

const breadcrumbs: AdminBreadcrumb[] = [{ label: '得意先管理' }];
const columnCount = 7;
const customers = ref<CursorPaginated<CustomerListItem> | null>(null);
const loading = ref(false);
const loadingNext = ref(false);
const errorMessage = ref('');
const failedCursor = ref<string | null>(null);
const scrollEndElement = ref<HTMLElement | null>(null);
let scrollObserver: IntersectionObserver | null = null;

const form = reactive<CustomerFilters>({
    keyword: props.filters.keyword,
    status: props.filters.status,
});

const hasRows = computed(() => (customers.value?.data.length ?? 0) > 0);
const hasMore = computed(() => customers.value !== null && customers.value.next_cursor !== null);

function syncUrl(): void {
    const params = new URLSearchParams();

    if (form.keyword !== '') {
        params.set('keyword', form.keyword);
    }
    if (form.status !== 'active') {
        params.set('status', form.status);
    }

    const query = params.toString();
    window.history.replaceState(
        window.history.state,
        '',
        query === '' ? window.location.pathname : `${window.location.pathname}?${query}`,
    );
}

async function fetchCustomers(cursor?: string): Promise<void> {
    const isNextPage = cursor !== undefined;
    loading.value = !isNextPage;
    loadingNext.value = isNextPage;
    errorMessage.value = '';
    failedCursor.value = cursor ?? null;

    try {
        const response = await api.get<{
            filters: CustomerFilters;
            customers: CursorPaginated<CustomerListItem>;
        }>('/admin/customers', {
            params: {
                keyword: form.keyword,
                status: form.status,
                ...(cursor === undefined ? {} : { cursor }),
            },
        });

        const nextPage = response.data.customers;
        customers.value = isNextPage && customers.value !== null
            ? { ...nextPage, data: [...customers.value.data, ...nextPage.data] }
            : nextPage;
        failedCursor.value = null;
    } catch (error: unknown) {
        errorMessage.value = getApiErrorMessage(error, '得意先を読み込めませんでした。');
    } finally {
        loading.value = false;
        loadingNext.value = false;
    }
}

function search(): void {
    form.keyword = form.keyword.trim();
    syncUrl();
    customers.value = null;
    void fetchCustomers();
}

function resetFilters(): void {
    form.keyword = '';
    form.status = 'active';
    search();
}

function loadNextPage(): void {
    const cursor = customers.value?.next_cursor;
    if (cursor !== null && cursor !== undefined && !loading.value && !loadingNext.value) {
        void fetchCustomers(cursor);
    }
}

function retry(): void {
    void fetchCustomers(failedCursor.value ?? undefined);
}

onMounted(() => {
    void fetchCustomers();

    if (scrollEndElement.value !== null && 'IntersectionObserver' in window) {
        scrollObserver = new IntersectionObserver((entries) => {
            if (entries.some((entry) => entry.isIntersecting)) {
                loadNextPage();
            }
        });
        scrollObserver.observe(scrollEndElement.value);
    }
});

onBeforeUnmount(() => {
    scrollObserver?.disconnect();
    scrollObserver = null;
});
</script>

<template>
    <AdminLayout
        title="得意先管理"
        lead="基幹システムに登録されている得意先を参照します。"
        :breadcrumbs="breadcrumbs"
    >
        <div
            v-if="errorMessage"
            class="alert alert-danger d-flex align-items-center justify-content-between gap-3"
            role="alert"
        >
            <span>{{ errorMessage }}</span>
            <button
                type="button"
                class="btn btn-outline-danger btn-sm text-nowrap"
                @click="retry"
            >
                再試行
            </button>
        </div>

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
                        <tbody>
                            <tr
                                v-for="customer in customers?.data ?? []"
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
                                v-if="loading && !hasRows"
                            >
                                <td
                                    :colspan="columnCount"
                                    class="text-center text-body-secondary py-4"
                                >
                                    <span
                                        class="spinner-border spinner-border-sm me-2"
                                        aria-hidden="true"
                                    />読み込み中…
                                </td>
                            </tr>
                            <tr
                                v-else-if="customers !== null && !hasRows"
                            >
                                <td
                                    :colspan="columnCount"
                                    class="text-center text-body-secondary py-3"
                                >
                                    条件に一致する得意先はありません。
                                </td>
                            </tr>
                            <tr v-if="loadingNext">
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
                        </tbody>
                    </table>
                </div>
            </div>
            <div
                ref="scrollEndElement"
                class="visually-hidden"
                aria-hidden="true"
            />
            <div
                class="card-footer text-body-secondary small"
                aria-live="polite"
            >
                <template v-if="customers === null && loading">
                    読み込み中…
                </template>
                <template v-else-if="customers === null && errorMessage">
                    得意先を読み込めませんでした。
                </template>
                <template v-else-if="customers !== null && !hasRows">
                    0件
                </template>
                <template v-else-if="hasMore">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <span>スクロールすると続きを読み込みます。</span>
                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm"
                            :disabled="loadingNext"
                            @click="loadNextPage"
                        >
                            さらに読み込む
                        </button>
                    </div>
                </template>
                <template v-else>
                    すべての得意先を表示しました。
                </template>
            </div>
        </div>
    </AdminLayout>
</template>
