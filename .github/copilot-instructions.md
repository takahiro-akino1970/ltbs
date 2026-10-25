# LTBS 開発 instructions

## プロジェクト概要

- LTBS（Land Transport Booking System）は陸送発注管理システム。
- 現在はアプリケーションひな型。認証、PWA、業務機能は未実装。
- 未実装機能や要件定義の初期案を、実装済み・確定済みとして扱わない。
- 技術構成の変更や新しいサービスの導入は、必要性を確認してから行う。

## 技術要素

| 領域 | 使用技術 |
| --- | --- |
| バックエンド | PHP 8.4、Laravel 13、PHP-FPM |
| フロントエンド連携 | Inertia.js 3（Laravel / Vue アダプター） |
| フロントエンド | Vue 3、TypeScript 5（strict モード） |
| スタイル | Sass（SCSS）、Bootstrap 5.3 |
| UI JavaScript | Bootstrap、Popper.js |
| ビルド・開発サーバー | Vite 8、Node.js 22.12以上、npm |
| Webサーバー | Nginx（非 root イメージ） |
| データベース | MariaDB 11.4 LTS、utf8mb4 / utf8mb4_unicode_ci |
| 開発環境 | Docker Compose v2 |
| フロントエンド検証 | ESLint、vue-tsc |
| PHP検証 | Laravel Pint、PHPUnit 12 |

バージョンや依存関係の正確な情報は `src/composer.json`、
`src/package.json` と各ロックファイルを参照する。

## 配置と実装方針

- Laravel アプリケーションは `src/`、Docker 設定は `docker/`、資料は `doc/` に配置する。
- Laravel のルートは `src/routes/web.php` に定義し、`Inertia::render()` で Vue ページを返す。
- ページは `src/resources/js/pages/` の Vue SFC に配置する。
- Vue は Composition API と `<script setup lang="ts">` を使用する。
- props やイベントには型を定義し、不要な `any` や型アサーションでエラーを回避しない。
- 独立した SPA や Vue Router は使用していない。既存の Inertia 構成に合わせる。
- JavaScript エントリーポイントは `src/resources/js/order.ts` と `src/resources/js/admin.ts`。
- Vue ページは `src/resources/js/pages/order/` と `src/resources/js/pages/admin/` に分ける。
- ポータル別スタイルは `src/resources/scss/order.scss` と `src/resources/scss/admin.scss` に配置する。
- Bootstrap の共通設定は `src/resources/scss/_bootstrap.scss` に配置し、各エントリーから `@use` で読み込む。
- アプリ共通スタイルは `src/resources/scss/app.scss` に配置する。
- Bootstrap の変数は `with (...)` でカスタマイズする。Vue SFC の SCSS も利用できる。
- ダークモードは `prefers-color-scheme` に連動する。
- Bootstrap Tooltip / Popover は必要な箇所で初期化し、Vue のアンマウント時に `dispose()` する。
- Vite の `quietDeps: true` は依存パッケージの Sass 警告用。アプリの警告を無効化しない。
- 既存の命名、フォーマット、日本語の画面文言に合わせる。
- 入力検証・認可は必要に応じて Laravel 側で実施し、フロントエンドだけに依存しない。
- 新規作成するテーブルには原則として nullable な `deleted_at` を追加し、論理削除を前提に設計する。明確に不要な理由がある場合のみ省略し、その理由を記録する。
- 論理削除の実装では、削除済みデータの参照・認証対象からの除外、一意制約、復元、初期登録済み判定への影響も確認する。

## Docker構成

- `app`：Laravel / PHP-FPM。内部ポート9000。ホストには公開しない。
- `nginx`：`src/public/` のみを読み取り専用でマウントし、PHP リクエストを `app:9000` に渡す。
- `vite`：フロントエンドの配信・ホットリロード。
- `mariadb`：Laravel の接続先。内部ポート3306。ホストには公開しない。
- アプリの既定URLは `http://localhost:8000`。Vite の既定ポートは5173。
- ポートは `docker/.env` の `APP_PORT` / `VITE_PORT` で変更できる。固定値を決めつけない。
- `src/node_modules` はホストとコンテナで共有する。npm 操作はコンテナ側に統一し、同時実行しない。
- Composer の `vendor` と MariaDB の `mariadb_data` は名前付きボリュームで保存する。
- ホストの UID/GID に合わせて `LOCAL_UID` / `LOCAL_GID` を設定する。
- この Compose は開発専用。起動時に依存関係をインストールするが、マイグレーションは自動実行しない。

## 開発・検証コマンド

以下はプロジェクトルートで実行する。Artisan は原則 `app` コンテナで実行する。
実行前に `.vscode/settings.json` とサービスの起動状態も確認する。

```bash
docker compose -f docker/compose.yaml up --build -d
docker compose -f docker/compose.yaml exec app php artisan route:list
docker compose -f docker/compose.yaml exec app php artisan migrate
docker compose -f docker/compose.yaml exec app composer require <package-name>
docker compose -f docker/compose.yaml exec vite npm install <package-name>
```

変更範囲に応じて必要な検証を実行する。

```bash
docker compose -f docker/compose.yaml exec vite npm run lint
docker compose -f docker/compose.yaml exec vite npm run typecheck
docker compose -f docker/compose.yaml exec vite npm run build
docker compose -f docker/compose.yaml exec app vendor/bin/pint --test
docker compose -f docker/compose.yaml exec app php artisan test
docker compose -f docker/compose.yaml exec nginx nginx -t
```

- PHP / Vue の挙動変更には関連テストを追加・更新する。
- UI変更は必要に応じてブラウザーでも確認する。
- PHPUnit は SQLite のメモリDBを使用し、開発用MariaDBには接続しない。
- `src/phpunit.xml` のDB分離設定と `DatabaseConnectionTest` の検証を維持する。
- 依存関係の変更時はマニフェストとロックファイルをセットで更新する。

## データ・秘密情報の扱い

- `.env`、認証情報、秘密鍵をコミットしない。設定例は `.env.example` に記載する。
- Docker のDB設定は Compose から渡され、`src/.env` の SQLite 設定より優先される。
- 旧SQLiteファイルは保持している。MariaDBへの自動データ移行は行わない。
- `docker compose down --volumes` は MariaDB と移行前MySQLの全データを削除するため、明示的な許可なく実行しない。
- `migrate:fresh`、SQLite削除、既存データの破棄も明示的な許可なく行わない。
- MariaDB 初期化用環境変数は空のボリュームの初期化時のみ反映される。
- 既存MariaDBの認証情報変更のために、データボリュームを削除しない。

## 関連資料

- [構成概要](../doc/architecture.md)
- [Docker環境](../doc/docker.md)
- [データベース](../doc/database.md)
- [開発ガイド](../doc/development.md)
- [要件定義書（たたき台）](../doc/要件定義書_たたき台.md)
