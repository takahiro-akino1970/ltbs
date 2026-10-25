# 開発ガイド

## Artisan / 依存パッケージ

以下の Docker Compose コマンドはプロジェクトの `docker/` で実行します。
環境の準備は [Docker環境](docker.md) を参照してください。

```bash
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate
docker compose exec app composer require <package-name>
docker compose exec vite npm install <package-name>
```

`composer.json` / `composer.lock` と `package.json` / `package-lock.json` を
それぞれセットで管理してください。
VS Code の Artisan 設定も Docker 実行用に更新しています。

## 検証

`docker/` で実行します。

```bash
docker compose exec vite npm run lint
docker compose exec vite npm run typecheck
docker compose exec vite npm run build
docker compose exec app vendor/bin/pint --test
docker compose exec app php artisan test
docker compose exec nginx nginx -t
```

VS Code の「Validate Laravel and Vue scaffold」タスクでもまとめて実行できます。
通常のHTML画面応答とJSON API応答を検証するFeatureテストを用意しています。
PHPUnit は Docker の DB 環境変数を上書きして SQLite のメモリDBを使用します。
テスト用接続が分離されていることも Feature テストで検証します。

### Viteを使わずビルド済みアセットで確認する

```bash
docker compose exec vite npm run build
docker compose stop vite
docker compose exec app php -r "if (file_exists('public/hot')) { unlink('public/hot'); }"
```

http://localhost:8000 を再読み込みすると、ビルド済みアセットで表示します。
開発に戻る場合は `docker compose up -d vite` を実行してください。

## Dockerを使わずに開発する

PHP 8.3 以上、Composer 2、Node.js 22.12 以上、npm が必要です。
PHP は Laravel / PHPUnit の必須拡張に加え、`pdo_sqlite` を有効にしてください。

プロジェクトの `src/` で実行します。

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
Docker では Compose の環境変数により MariaDB を使用します。
このローカル開発手順と PHPUnit は SQLite を使用します。

ローカルでの検証は `src/` で実行します。

```bash
npm run lint
npm run typecheck
npm run build
vendor/bin/pint --test
php artisan test
```

## Sass / Bootstrap 5

JavaScript は発注側の `src/resources/js/order.ts` と管理側の
`src/resources/js/admin.ts`、CSS は発注側の `src/resources/scss/order.scss` と
管理側の `src/resources/scss/admin.scss` をそれぞれ独立した Vite エントリーとして
コンパイルし、`APP_PORTAL` に応じて対応するエントリーの組み合わせだけを読み込みます。
Vue ページは `src/resources/js/pages/order/` と `src/resources/js/pages/admin/` に
分け、共通のVue起動処理は `src/resources/js/createPortalApp.ts` に配置します。
Bootstrap の共通設定は `src/resources/scss/_bootstrap.scss`、アプリ共通スタイルは
`src/resources/scss/app.scss` に配置し、両エントリーから読み込みます。
Sass は Vite がコンパイルするため、追加の Sass 起動コマンドは不要です。
Vue SFC の `<style lang="scss" scoped>` も使用できます。

各ポータルの SCSS は `_bootstrap.scss` を `@use "bootstrap" as bootstrap` で読み込みます。
Bootstrap の変数・Mixin・関数は `bootstrap.$primary` や
`@include bootstrap.media-breakpoint-up(md) { ... }` のように参照できます。
共通の Bootstrap 変数は `_bootstrap.scss` の `with (...)` で上書きしてください。
ダークモードは OS の設定（`prefers-color-scheme`）に連動します。
Popper も導入しているため、Dropdown などの位置調整が必要なコンポーネントも利用できます。
Tooltip / Popover は自動初期化されないため、Vue のマウント時に初期化し、
アンマウント時に `dispose()` してください。

ホーム画面の表示／非表示ボタンは Bootstrap の Collapse の動作サンプルです。
Bootstrap 5 内部には旧 Sass 記法が残っているため、Vite の `quietDeps: true` で
依存パッケージ内の Sass 警告を抑制しています。アプリ独自の SCSS の警告・エラーは抑制しません。

## Figma MCPを使った画面実装

Figma MCPを利用できるVS Code環境では、画面のデザインを参照しながら実装できます。
MCPサーバーの利用可否や認証状態は環境に依存します。接続や認証を求められた場合は、
VS CodeのMCP設定に従って準備してください。アクセストークンなどの認証情報は
リポジトリに保存しないでください。

1. 対象画面またはコンポーネントのFigmaリンクを共有します。特定の要素を参照する場合は、
   `node-id` を含むリンクを使用してください。リンクに対象ノードが指定されていない場合は、
   推測せず対象を確認してください。
2. Figma MCPのデザインコンテキスト取得機能で、対象ノードの情報、画面画像、
   必要なメタデータやアセットを取得します。
   参照先へのアクセス権限がない場合は、権限のあるリンクを用意してください。
3. 取得した情報を参考に、既存のVueページ、共通コンポーネント、SCSS、
   Bootstrap設定に合わせて実装します。生成されたコードはそのまま採用せず、
   LTBSの構成・命名・アクセシビリティ・レスポンシブ対応に適合させてください。
4. 必要なアセットだけをプロジェクトの適切な場所に取り込み、利用条件を確認します。
   デザイン上の表示だけから、未確定の業務仕様やAPI要件を確定したものとして扱わないでください。
5. 実装後はブラウザーでデザインと表示を確認し、変更範囲に応じて
   `npm run lint`、`npm run typecheck`、`npm run build` を実行します。

Figma MCPによるデザインの更新・書き戻しは、依頼で明示された場合に限って行います。
画面実装の確認に使う場合は、既存のFigmaファイルを変更しないでください。

## 依存関係の監査

作成時の `npm audit` / `composer audit` は警告 0 件です。
一方、VS Code の Dockerfile 診断では、公式 Node.js / PHP の
ベースイメージに critical / high の脆弱性警告が報告されています。
Nginx・MariaDB のイメージも含め、すべてのベースイメージを監査対象としてください。
パッケージ監査とは別の警告であり、この構成は本番用の安全性検証済みではありません。
本番環境を構築する際はベースイメージの更新と再スキャンを行ってください。

## 関連資料

- [構成概要](architecture.md)
- [Docker環境](docker.md)
- [データベース](database.md)
- [資料一覧](../README.md)
