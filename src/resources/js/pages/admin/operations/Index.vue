<script setup lang="ts">
import { ref } from 'vue';
import AdminLayout from '../../../layouts/admin/AdminLayout.vue';
import type { AdminBreadcrumb } from '../../../types/admin';

const breadcrumbs: AdminBreadcrumb[] = [{ label: '輸送進捗' }];
const views = ['一覧', 'ボード', 'スケジュール'];
const activeView = ref(views[0]);

const progressGroups = [
    { label: '受注済み', count: 8, tone: 'border-primary' },
    { label: '引取準備中', count: 3, tone: 'border-warning' },
    { label: '輸送中', count: 5, tone: 'border-info' },
    { label: '完了', count: 24, tone: 'border-success' },
];
</script>

<template>
    <AdminLayout
        title="輸送進捗"
        lead="受注後の発注をステータス別に確認します。"
        :breadcrumbs="breadcrumbs"
    >
        <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
            <div
                class="btn-group btn-group-sm"
                role="group"
                aria-label="表示形式"
            >
                <button
                    v-for="view in views"
                    :key="view"
                    type="button"
                    class="btn btn-outline-primary"
                    :class="{ active: view === activeView }"
                    @click="activeView = view"
                >
                    {{ view }}
                </button>
            </div>
            <span class="text-body-secondary small align-self-center">
                表示切替はひな型です。
            </span>
        </div>

        <div class="row g-3 mb-3">
            <div
                v-for="group in progressGroups"
                :key="group.label"
                class="col-6 col-xl-3"
            >
                <div
                    class="card h-100 border-start border-4"
                    :class="group.tone"
                >
                    <div class="card-body">
                        <p class="text-body-secondary small mb-1">
                            {{ group.label }}
                        </p>
                        <p class="h4 mb-0">
                            {{ group.count }}件
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                {{ activeView }}表示
            </div>
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
                                引取予定
                            </th>
                            <th scope="col">
                                納車予定
                            </th>
                            <th scope="col">
                                進捗
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <a href="/orders/ORD-2026-002">
                                    ORD-2026-002
                                </a>
                            </td>
                            <td>テスト商事株式会社</td>
                            <td class="text-nowrap">
                                2026/10/16 13:00
                            </td>
                            <td class="text-nowrap">
                                2026/10/18 10:00
                            </td>
                            <td><span class="badge text-bg-primary">受注済み</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-body-secondary small">
                サンプルデータです。ボード・スケジュール表示は未実装です。
            </div>
        </div>
    </AdminLayout>
</template>
