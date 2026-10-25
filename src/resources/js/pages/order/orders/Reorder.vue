<script setup lang="ts">
import OrderLayout from '../../../layouts/order/OrderLayout.vue';
import type { OrderBreadcrumb } from '../../../types/order';

const props = defineProps<{
    orderNumber: string;
}>();

const breadcrumbs: OrderBreadcrumb[] = [
    { label: '発注履歴', href: '/orders' },
    { label: props.orderNumber, href: `/orders/${props.orderNumber}` },
    { label: '再発注' },
];
</script>

<template>
    <OrderLayout
        title="再発注"
        :lead="`発注 ${orderNumber} の内容を引き継いで、新しい発注を作成します。`"
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
                <div class="card">
                    <div class="card-header">
                        引き継ぐ内容
                    </div>
                    <div class="card-body text-body-secondary small">
                        引取情報・納車情報・車両情報の複写は未実装です。
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-5">
                <div class="card order-preview">
                    <div class="card-header">
                        プレビュー
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
