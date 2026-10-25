<script setup lang="ts">
import OrderLayout from '../../../layouts/order/OrderLayout.vue';
import type { OrderBreadcrumb } from '../../../types/order';

const breadcrumbs: OrderBreadcrumb[] = [
    { label: '発注履歴', href: '/orders' },
    { label: '新規発注' },
];

const sections = [
    { title: '引取情報', description: '引取先の地点、希望日時、担当者を入力します。' },
    { title: '納車情報', description: '納車先の地点、希望日時、担当者を入力します。' },
    { title: '車両情報', description: '車両の種別、車両番号、台数、状態を入力します。' },
    { title: '連絡事項', description: '鍵の受け渡しや注意事項などを入力します。' },
];
</script>

<template>
    <OrderLayout
        title="新規発注"
        lead="入力内容は右側のプレビューに即時反映されます。確認用の別画面はありません。"
        :breadcrumbs="breadcrumbs"
    >
        <template #actions>
            <button
                type="button"
                class="btn btn-outline-secondary btn-sm"
                disabled
            >
                下書き保存
            </button>
            <button
                type="button"
                class="btn btn-primary btn-sm"
                disabled
            >
                発注する
            </button>
        </template>

        <div class="row g-3">
            <div class="col-12 col-xl-7">
                <div
                    v-for="section in sections"
                    :key="section.title"
                    class="card mb-3"
                >
                    <div class="card-header">
                        {{ section.title }}
                    </div>
                    <div class="card-body text-body-secondary small">
                        {{ section.description }}
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card order-preview">
                    <div class="card-header d-flex align-items-center justify-content-between">
                        <span>プレビュー</span>
                        <span class="badge text-bg-secondary fw-normal">未入力</span>
                    </div>
                    <div class="card-body text-body-secondary small">
                        入力した内容がここに表示されます。
                    </div>
                </div>
            </div>
        </div>
    </OrderLayout>
</template>

<style scoped lang="scss">
.order-preview {
    position: sticky;
    top: 4.5rem;
}
</style>
