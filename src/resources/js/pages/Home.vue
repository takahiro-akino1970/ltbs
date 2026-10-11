<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    appName: string;
}>();
</script>

<template>
    <Head title="陸送発注管理システム" />
    <main class="main">
        <p class="label">
            {{ appName }} · Land Transport Booking System
        </p>
        <h1>陸送発注管理システム</h1>
        <p class="description">
            Laravel + Inertia.js + Vue 3 + TypeScript の開発環境が起動しました。
            このひな型を起点に、発注・受注管理機能を実装できます。
        </p>
        <button
            type="button"
            class="btn btn-outline-primary mt-3"
            data-bs-toggle="collapse"
            data-bs-target="#environment-features"
            aria-expanded="true"
            aria-controls="environment-features"
        >
            開発環境の構成を表示／非表示
        </button>
        <div
            id="environment-features"
            class="collapse show"
        >
            <ul
                class="features"
                aria-label="開発環境の構成"
            >
                <li>Laravel</li>
                <li>Inertia.js</li>
                <li>Vue 3</li>
                <li>TypeScript（strict モード）</li>
                <li>Sass / Bootstrap 5</li>
                <li>Vite / Docker Compose</li>
            </ul>
        </div>
        <p class="note">
            現在はひな型のみです。認証・PWA・業務機能は未実装です。
        </p>
    </main>
</template>
