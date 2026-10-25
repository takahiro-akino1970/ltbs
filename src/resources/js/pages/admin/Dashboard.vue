<script setup lang="ts">
import AdminLayout from '../../layouts/admin/AdminLayout.vue';

const statusSummary = [
    { label: '新規発注', count: 3, tone: 'text-bg-danger' },
    { label: '仮発注', count: 2, tone: 'text-bg-warning' },
    { label: '受注済み', count: 8, tone: 'text-bg-primary' },
    { label: '輸送中', count: 5, tone: 'text-bg-info' },
    { label: '完了', count: 24, tone: 'text-bg-success' },
    { label: 'キャンセル', count: 1, tone: 'text-bg-secondary' },
];

const todaySummary = [
    { label: '本日引取予定', count: 4 },
    { label: '本日納車予定', count: 3 },
    { label: '要確認', count: 2 },
];
</script>

<template>
    <AdminLayout
        title="ダッシュボード"
        lead="受注・輸送状況と、対応が必要な発注を確認します。"
    >
        <template #actions>
            <a
                href="/orders"
                class="btn btn-primary btn-sm"
            >
                発注管理
            </a>
            <a
                href="/operations"
                class="btn btn-outline-secondary btn-sm"
            >
                輸送進捗
            </a>
        </template>

        <div class="row g-3 mb-3">
            <div
                v-for="status in statusSummary"
                :key="status.label"
                class="col-6 col-md-4 col-xl-2"
            >
                <div class="card h-100">
                    <div class="card-body">
                        <span
                            class="badge mb-2"
                            :class="status.tone"
                        >{{ status.label }}</span>
                        <p class="h4 mb-0">
                            {{ status.count }}
                            <span class="fs-6 fw-normal text-body-secondary">件</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>対応が必要な発注</span>
                        <span class="badge text-bg-danger">3件</span>
                    </div>
                    <div class="list-group list-group-flush">
                        <a
                            href="/orders/ORD-2026-001"
                            class="list-group-item list-group-item-action"
                        >
                            <div class="d-flex justify-content-between gap-3">
                                <span>ORD-2026-001 — サンプル運輸株式会社</span>
                                <span class="badge text-bg-danger">未対応</span>
                            </div>
                            <small class="text-body-secondary">引取希望: 2026/10/16 09:00</small>
                        </a>
                        <div class="list-group-item text-body-secondary small">
                            この一覧は表示確認用のサンプルです。
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card h-100">
                    <div class="card-header">
                        本日の予定
                    </div>
                    <div class="card-body">
                        <dl class="mb-0">
                            <div
                                v-for="item in todaySummary"
                                :key="item.label"
                                class="d-flex justify-content-between border-bottom py-2"
                            >
                                <dt class="fw-normal">
                                    {{ item.label }}
                                </dt>
                                <dd class="mb-0 fw-bold">
                                    {{ item.count }}件
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
