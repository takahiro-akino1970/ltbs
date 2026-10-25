<script setup lang="ts">
import { onMounted } from 'vue';
import { useAppConfig } from '../../../appConfig';

const { appName } = useAppConfig();

onMounted(() => {
    document.title = `管理側ログイン | ${appName}`;
});
</script>

<template>
    <main class="min-vh-100 d-flex align-items-center justify-content-center bg-body-tertiary p-3">
        <div class="card shadow-sm w-100 login-card">
            <div class="card-body p-4">
                <p class="badge text-bg-secondary mb-3">
                    受注・システム管理側
                </p>
                <h1 class="h5 mb-1">
                    {{ appName }}
                </h1>
                <p class="text-body-secondary small mb-4">
                    管理側ポータルにログインします。
                </p>

                <form @submit.prevent>
                    <div class="mb-3">
                        <label
                            for="admin-login-id"
                            class="form-label"
                        >ログインID</label>
                        <input
                            id="admin-login-id"
                            type="text"
                            class="form-control"
                            autocomplete="username"
                            disabled
                        >
                    </div>

                    <div class="mb-3">
                        <label
                            for="admin-login-password"
                            class="form-label"
                        >パスワード</label>
                        <input
                            id="admin-login-password"
                            type="password"
                            class="form-control"
                            autocomplete="current-password"
                            disabled
                        >
                    </div>

                    <div class="form-check mb-3">
                        <input
                            id="admin-login-remember"
                            type="checkbox"
                            class="form-check-input"
                            disabled
                        >
                        <label
                            for="admin-login-remember"
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
