# LTBS

Land Transport Booking System（陸送発注管理システム）のひな型です。
Laravel 13 + Inertia.js 3 + Vue 3 + TypeScript を使用します。
スタイルは Sass（SCSS）と Bootstrap 5.3 を使用します。
前回の Next.js / React 構成は置き換え済みです。
認証、PWA、業務機能はまだ実装していません。

## ディレクトリ構成

```text
.
├── docker/
│   ├── Dockerfile         # PHP 8.4-FPM / Node.js 22 の開発イメージ
│   ├── compose.yaml       # Nginx・Laravel（PHP-FPM）・Vite・MySQL の4サービス
│   ├── nginx/default.conf # 公開ディレクトリと FastCGI の設定
│   ├── php-fpm.conf       # PHP-FPM の設定
│   ├── entrypoint.sh      # Laravel の初期化と PHP-FPM の起動
│   └── .env.example       # ポート・ユーザーID・DB設定例
├── src/
│   ├── app/               # Laravel のアプリケーション
│   ├── routes/web.php     # Inertia ページのルーティング
│   ├── resources/
│   │   ├── js/app.ts      # Vue / Inertia のエントリーポイント
│   │   ├── js/pages/      # Vue SFC（TypeScript）
│   │   ├── scss/app.scss  # Bootstrap とアプリ独自のスタイル
│   │   └── views/app.blade.php
│   ├── database/          # マイグレーション・旧SQLiteファイル
│   └── tests/
└── doc/                   # 既存の要件定義・参考資料
```

Laravel のルートから `Inertia::render()` で Vue ページを返す構成です。
独立した SPA 用 API サーバーや Vue Router は使用していません。

Web サーバーは Nginx です。PHP 内蔵の開発用 Web サーバーは使用しません。
Nginx が静的ファイルを配信し、Laravel へのリクエストを
`app:9000` の PHP-FPM に FastCGI で渡します。
PHP-FPM のポートはホストに公開せず、Nginx のみを `APP_PORT` で公開します。
Nginx には `src/public/` だけを読み取り専用でマウントしています。

## Docker で開発する

Docker Engine / Docker Desktop と Docker Compose v2 が必要です。
ホスト側に PHP、Composer、Node.js をインストールする必要はありません。

プロジェクトルートから実行します。

```bash
cd docker
cp .env.example .env
```

初回セットアップ時のみコピーしてください。既存の `.env` は上書きしないでください。
`docker/.env` の `MYSQL_PASSWORD` / `MYSQL_ROOT_PASSWORD` に、それぞれ異なる
十分に長いランダムなパスワードを設定してください（`openssl rand -hex 24` などで生成）。
未設定では Compose が明示的にエラーになります。パスワードを Git にコミットしないでください。
この作業環境では、Git 管理対象外の `docker/.env` に生成済みです。

```bash
docker compose up --build
```

初回起動時に以下を自動実行します。

- `composer install` / `npm ci` でロックファイルの依存関係をインストール
- `src/.env` が存在しなければ `src/.env.example` から作成
- `APP_KEY` が未設定なら生成
- MySQL のデータベース・アプリ用ユーザーを初回作成
- MySQL の接続準備完了後に Laravel のマイグレーションを実行

4サービスの準備完了後、**http://localhost:8000** を開いてください。
Vite は http://localhost:5173 で動作します（アプリの閲覧先ではありません）。
この作業環境では 5173 が使用中だったため、Git 管理対象外の
`docker/.env` で `VITE_PORT=5174` に設定しています。
ブラウザーのオリジンと Vite の CORS 設定を一致させるため、
`127.0.0.1` ではなく `localhost` でアクセスしてください。
公開ポートはホストのループバックアドレスに限定しています。

この環境は開発専用です。本番では使用しないでください。
起動時のマイグレーションは開発用データベースを対象にしています。
既存・本番データベースに接続する場合は自動実行を見直してください。

### ファイルとデータの保存

- `src/` を `/app` にマウントし、Vue / TypeScript は Vite の HMR で更新します。
- PHP / Blade ファイルの変更時はページがリロードされます。
- Composer の `vendor` は専用ボリュームに分離します。
- npm の `node_modules` は `src/node_modules` をホストとコンテナで共有します。
  Vite 起動時の `npm ci` でインストールされ、ホスト側の VS Code も型定義を参照できます。
  npm のインストール操作はコンテナ側に統一し、ホストとの同時実行を避けてください。
- MySQL のデータは `mysql_data` の名前付きボリュームに保存します。
- `.env`、旧SQLite（`src/database/database.sqlite`）、`storage/` はホストに保存します。
- SQLite ファイルは保持しますが、データを MySQL に自動移行しません。
- SQLite と秘密情報を含む `.env` は Git 管理対象外です。
- Nginx・PHP-FPM・Vite は非 root ユーザーで動作します。
  MySQL は公式イメージの初期化後、mysqld を mysql ユーザーで実行します。

Linux でホストの UID/GID が 1000 以外の場合は、起動前に
`docker/.env.example` を `docker/.env` にコピーし、
`LOCAL_UID` / `LOCAL_GID` を `id -u` / `id -g` の値に設定してください。
変更後はイメージを再ビルドしてください。

### 起動・停止・ログ

以下は `docker/` で実行します。

```bash
docker compose up --build -d
docker compose logs -f nginx app vite mysql
docker compose down
```

`docker compose down` は MySQL、SQLite、依存パッケージのデータを削除しません。
**`docker compose down --volumes` は MySQL の全データと `vendor` を削除します。**
共有する `src/node_modules` はこのコマンドでは削除されません。
SQLite の削除や `migrate:fresh` は既存データを失うため、必要な場合だけ実行してください。

### ポートを変更する

`docker/.env` の `APP_PORT`（既定 8000）と `VITE_PORT`（既定 5173）を変更し、
`docker compose up -d` で再作成してください。
Laravel の `APP_URL`、Vite の CORS、HMR ポートも Compose から連動して設定します。
`src/.env` の `APP_URL` は Docker 以外で実行する場合にも合わせて変更してください。

### MySQL への接続

MySQL 8.4 LTS を使用します。Docker 内の Laravel は以下の接続設定を使用します。

| 設定                  | 値                                               |
| --------------------- | ------------------------------------------------ |
| ホスト                | `mysql`                                          |
| ポート                | `3306`（Docker 内部のみ）                        |
| データベース          | `MYSQL_DATABASE`（既定 `ltbs`）                  |
| ユーザー              | `MYSQL_USER`（既定 `ltbs`、root は使用しません） |
| パスワード            | `docker/.env` の `MYSQL_PASSWORD`                |
| 文字コード / 照合順序 | `utf8mb4` / `utf8mb4_unicode_ci`                 |

Compose が Laravel に環境変数を渡すため、Docker では `src/.env` の
SQLite 設定より MySQL 設定が優先されます。ローカル開発と PHPUnit のテストは
引き続き SQLite を使用できるよう `pdo_sqlite` も残しています。

MySQL はホストに公開していません。CLI で接続する場合は `docker/` で実行します。
パスワードはプロンプトに `MYSQL_PASSWORD` の値を入力してください。

```bash
docker compose exec mysql mysql -u ltbs -p ltbs
docker compose exec app php artisan migrate:status
```

ユーザー名 / DB 名を変更した場合は上記の `ltbs` を読み替えてください。
MySQL イメージの初期化用環境変数は、空のボリュームを初期化するときだけ反映されます。
既存の DB に対するパスワードやユーザー変更は SQL で行い、
`docker/.env` も合わせて更新してください。データ保持のためにボリュームを削除してはいけません。

### Artisan / 依存パッケージ

```bash
docker compose exec app php artisan route:list
docker compose exec app php artisan migrate
docker compose exec app composer require <package-name>
docker compose exec vite npm install <package-name>
```

`composer.json` / `composer.lock` と `package.json` / `package-lock.json` を
それぞれセットで管理してください。
VS Code の Artisan 設定も Docker 実行用に更新しています。

### 検証

```bash
docker compose exec vite npm run lint
docker compose exec vite npm run typecheck
docker compose exec vite npm run build
docker compose exec app vendor/bin/pint --test
docker compose exec app php artisan test
docker compose exec nginx nginx -t
```

VS Code の「Validate Laravel and Vue scaffold」タスクでもまとめて実行できます。
Inertia の HTML 応答・JSON 応答を検証する Feature テストを用意しています。
PHPUnit は Docker の MySQL 環境変数を上書きして SQLite のメモリDBを使用します。
テスト用接続が分離されていることも Feature テストで検証します。

### Vite を使わずビルド済みアセットで確認する

```bash
docker compose exec vite npm run build
docker compose stop vite
docker compose exec app php -r "if (file_exists('public/hot')) { unlink('public/hot'); }"
```

http://localhost:8000 を再読み込みすると、ビルド済みアセットで表示します。
開発に戻る場合は `docker compose up -d vite` を実行してください。

## Docker を使わずに開発する

[アプリの README](src/README.md) を参照してください。

## 依存関係の監査

作成時の `npm audit` / `composer audit` は警告 0 件です。
一方、VS Code の Dockerfile 診断では、公式 Node.js / PHP の
ベースイメージに critical / high の脆弱性警告が報告されています。
Nginx・MySQL のイメージも含め、すべてのベースイメージを監査対象としてください。
パッケージ監査とは別の警告であり、この構成は本番用の安全性検証済みではありません。
本番環境を構築する際はベースイメージの更新と再スキャンを行ってください。

# ltbs

Land Transport Booking System
