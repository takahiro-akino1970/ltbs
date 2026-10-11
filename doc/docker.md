# Docker環境

## 必要な環境

Docker Engine / Docker Desktop と Docker Compose v2 が必要です。
ホスト側に PHP、Composer、Node.js をインストールする必要はありません。

この環境は開発専用です。本番では使用しないでください。
起動時のマイグレーションは開発用データベースを対象にしています。
既存・本番データベースに接続する場合は自動実行を見直してください。

## 初回セットアップ

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

Linux でホストの UID/GID が 1000 以外の場合は、起動前に
`LOCAL_UID` / `LOCAL_GID` を `id -u` / `id -g` の値に設定してください。
変更後はイメージを再ビルドしてください。

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

## ファイルとデータの保存

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

## 起動・停止・ログ

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

## ポートを変更する

`docker/.env` の `APP_PORT`（既定 8000）と `VITE_PORT`（既定 5173）を変更し、
`docker compose up -d` で再作成してください。
Laravel の `APP_URL`、Vite の CORS、HMR ポートも Compose から連動して設定します。
`src/.env` の `APP_URL` は Docker 以外で実行する場合にも合わせて変更してください。

## 関連資料

- [構成概要](architecture.md)
- [MySQLへの接続](database.md)
- [開発・検証手順](development.md)
- [資料一覧](../README.md)
