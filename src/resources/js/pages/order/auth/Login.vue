<script setup lang="ts">
import { computed } from 'vue';
import { Head, usePage } from '@inertiajs/vue3';

const page = usePage();

const appName = computed(() => page.props.appName);
</script>

<template>
    <Head title="ログイン" />

    <main class="min-vh-100 d-flex align-items-center justify-content-center bg-body-tertiary p-3">
        <div class="card shadow-sm w-100 login-card">
            <div class="card-body p-4">
                <h1 class="h5 mb-1">
                    {{ appName }}
                </h1>
                <p class="text-body-secondary small mb-4">
                    発注側ポータルにログインします。
                </p>

                <form @submit.prevent>
                    <div class="mb-3">
                        <label
                            for="login-email"
                            class="form-label"
                        >メールアドレス</label>
                        <input
                            id="login-email"
                            type="email"
                            class="form-control"
                            autocomplete="username"
                            disabled
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            for="login-password"
                            class="form-label"
                        >パスワード</label>
                        <input
                            id="login-password"
                            type="password"
                            class="form-control"
                            autocomplete="current-password"
                            disabled
                        >
                    </div>

                    <div class="form-check mb-3">
                        <input
                            id="login-remember"
                            type="checkbox"
                            class="form-check-input"
                            disabled
                        >
                        <label
                            for="login-remember"
                            class="form-check-label"
                        >ログイン状態を保持する</label>
                    </div>

                    <button
                        type="submit"
                        class="btn btn-primary w-100"
                        disabled
                    >
                        ログイン
                    </button>
                </form>

                <p class="text-body-secondary small mt-4 mb-0">
                    認証は未実装です。画面のひな型のみ提供しています。
                </p>
            </div>
        </div>
    </main>
</template>

<style scoped lang="scss">
.login-card {
    max-width: 24rem;
}
</style>
