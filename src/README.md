# LTBS アプリケーション

Laravel 13 + Inertia.js 3 + Vue 3 + TypeScript のひな型です。
通常は [ルートの README](../README.md) の Docker 開発手順を利用してください。

## ローカル開発

PHP 8.4 以上、Composer 2、Node.js 22.12 以上、npm が必要です。
PHP は Laravel / PHPUnit の必須拡張に加え、`pdo_sqlite` を有効にしてください。

このディレクトリで実行します。

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
php -r "if (!file_exists('database/database.sqlite')) { touch('database/database.sqlite'); }"
php artisan migrate
```

既存の `.env` がある場合は上書きせず、`APP_KEY` も再生成しないでください。

別々のターミナルで起動します。

```bash
php artisan serve --host=localhost --port=8000
```

```bash
npm run dev
```

http://localhost:8000 を開いてください。
Docker と同時に同じソースディレクトリでサーバーを起動しないでください。
`public/hot` や SQLite ファイルが共有されるためです。
Docker では Compose の環境変数により MySQL を使用します。
このローカル開発手順と PHPUnit は SQLite を使用します。

## 検証

```bash
npm run lint
npm run typecheck
npm run build
vendor/bin/pint --test
php artisan test
```

## Sass / Bootstrap 5

`resources/js/app.ts` から `resources/scss/app.scss` と Bootstrap の JavaScript を
読み込みます。Sass は Vite がコンパイルするため、追加の Sass 起動コマンドは不要です。
Vue SFC の `<style lang="scss" scoped>` も使用できます。

Bootstrap の Sass は `app.scss` の `@use` で読み込みます。
Bootstrap 変数は `with (...)` で上書きし、独自スタイルはその後に記述してください。
ダークモードは OS の設定（`prefers-color-scheme`）に連動します。
Popper も導入しているため、Dropdown などの位置調整が必要なコンポーネントも利用できます。
Tooltip / Popover は自動初期化されないため、Vue のマウント時に初期化し、
アンマウント時に `dispose()` してください。

ホーム画面の表示／非表示ボタンは Bootstrap の Collapse の動作サンプルです。
Bootstrap 5 内部には旧 Sass 記法が残っているため、Vite の `quietDeps: true` で
依存パッケージ内の Sass 警告を抑制しています。アプリ独自の SCSS の警告・エラーは抑制しません。
