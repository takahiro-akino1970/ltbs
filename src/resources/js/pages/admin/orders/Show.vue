<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '../../../layouts/admin/AdminLayout.vue';
import type { AdminBreadcrumb } from '../../../types/admin';

const props = defineProps<{
    orderNumber: string;
}>();

const breadcrumbs: AdminBreadcrumb[] = [
    { label: '発注管理', href: '/orders' },
    { label: props.orderNumber },
];

const tabs = ['発注概要', '車両情報', '引取・納車', 'ルート', '連絡', '操作履歴'];
const activeTab = ref(tabs[0]);
</script>

<template>
    <AdminLayout
        :title="`発注詳細 ${orderNumber}`"
        lead="発注内容を確認し、右側のパネルから受注・進捗操作を行います。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <p class="text-body-secondary small mb-1">
                                    得意先
                                </p>
                                <p class="mb-0">
                                    サンプル運輸株式会社
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-body-secondary small mb-1">
                                    発注者
                                </p>
                                <p class="mb-0">
                                    山田 太郎
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-body-secondary small mb-1">
                                    引取希望
                                </p>
                                <p class="mb-0">
                                    2026/10/16 09:00
                                </p>
                            </div>
                            <div class="col-sm-6">
                                <p class="text-body-secondary small mb-1">
                                    納車希望
                                </p>
                                <p class="mb-0">
                                    2026/10/17 15:00
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

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
                    <div class="card-body">
                        <template v-if="activeTab === '発注概要'">
                            <dl class="row mb-0">
                                <dt class="col-sm-3">
                                    車両番号
                                </dt>
                                <dd class="col-sm-9">
                                    品川 300 あ 12-34
                                </dd>
                                <dt class="col-sm-3">
                                    引取先
                                </dt>
                                <dd class="col-sm-9">
                                    東京都品川区 サンプル引取センター
                                </dd>
                                <dt class="col-sm-3">
                                    納車先
                                </dt>
                                <dd class="col-sm-9">
                                    神奈川県横浜市 サンプル納車センター
                                </dd>
                                <dt class="col-sm-3">
                                    備考
                                </dt>
                                <dd class="col-sm-9 mb-0">
                                    表示確認用のサンプル発注です。
                                </dd>
                            </dl>
                        </template>
                        <p
                            v-else
                            class="text-body-secondary mb-0"
                        >
                            「{{ activeTab }}」の内容は未実装です。
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <aside class="card operation-panel">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>受注・進捗操作</span>
                        <span class="badge text-bg-danger">新規発注</span>
                    </div>
                    <div class="card-body">
                        <dl class="small">
                            <div class="d-flex justify-content-between gap-2 mb-2">
                                <dt>受注担当者</dt>
                                <dd class="mb-0 text-body-secondary">
                                    未設定
                                </dd>
                            </div>
                            <div class="d-flex justify-content-between gap-2">
                                <dt>受注日時</dt>
                                <dd class="mb-0 text-body-secondary">
                                    未受注
                                </dd>
                            </div>
                        </dl>

                        <button
                            type="button"
                            class="btn btn-primary w-100 mb-3"
                            disabled
                        >
                            受注する
                        </button>

                        <div class="mb-3">
                            <label
                                for="admin-order-status-update"
                                class="form-label small"
                            >ステータス更新</label>
                            <select
                                id="admin-order-status-update"
                                class="form-select form-select-sm"
                                disabled
                            >
                                <option>次のステータスを選択</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label
                                for="admin-order-message"
                                class="form-label small"
                            >発注者への連絡</label>
                            <textarea
                                id="admin-order-message"
                                class="form-control form-control-sm"
                                rows="3"
                                placeholder="連絡内容"
                                disabled
                            />
                        </div>

                        <button
                            type="button"
                            class="btn btn-outline-primary btn-sm w-100"
                            disabled
                        >
                            連絡を送信
                        </button>
                    </div>
                    <div class="card-footer text-body-secondary small">
                        受注・更新・送信処理は未実装です。
                    </div>
                </aside>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped lang="scss">
.operation-panel {
    position: sticky;
    top: 4.5rem;
}
</style>
