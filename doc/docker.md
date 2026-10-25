# Docker環境

## 必要な環境

Docker Engine / Docker Desktop と Docker Compose v2 が必要です。
ホスト側に PHP、Composer、Node.js をインストールする必要はありません。

この環境は開発専用です。本番では使用しないでください。
起動時にマイグレーションは実行しません。必要な場合は明示的に実行してください。

## 初回セットアップ

プロジェクトルートから実行します。

```bash
cd docker
cp .env.example .env
```

初回セットアップ時のみコピーしてください。既存の `.env` は上書きしないでください。
`docker/.env` の `MARIADB_PASSWORD` / `MARIADB_ROOT_PASSWORD` に、それぞれ異なる
十分に長いランダムなパスワードを設定してください（`openssl rand -hex 24` などで生成）。
未設定では Compose が明示的にエラーになります。パスワードを Git にコミットしないでください。
この作業環境では、Git 管理対象外の `docker/.env` に生成済みです。

Linux でホストの UID/GID が 1000 以外の場合は、起動前に
`LOCAL_UID` / `LOCAL_GID` を `id -u` / `id -g` の値に設定してください。
変更後はイメージを再ビルドしてください。

初回起動前に、ローカル用のアプリ設定ファイルを作成します。

```bash
cp -n local/order.env.example local/order.env
cp -n local/admin.env.example local/admin.env
```

それぞれのファイルで個別にLaravelキーを生成し、`APP_KEY` に設定します。
Laravelの暗号化も独立させる場合は同じキーを使い回さないでください。一方の環境で
Laravelにより暗号化された値は、他方の環境では復号できません。

```bash
for file in local/order.env local/admin.env; do
    if ! grep -q '^APP_KEY=.' "$file"; then
        key="base64:$(openssl rand -base64 32 | tr -d '\n')"
        sed -i "s|^APP_KEY=.*|APP_KEY=${key}|" "$file"
    fi
done
docker compose up --build
```

既存の `APP_KEY` がある場合はこのコマンドで上書きしません。

初回起動時に以下を自動実行します。

- `composer install` / `npm ci` でロックファイルの依存関係をインストール
- `app-order` と `app-admin` に同じソースコードをマウントし、設定は別々に環境変数として渡す
- MariaDB のデータベース・アプリ用ユーザーを初回作成

マイグレーションが必要な場合は、MariaDB の起動完了後に明示的に実行します。

```bash
docker compose exec app-order php artisan migrate
```

ブラウザーでは次のURLを使用します。

- 発注側：**http://order.localhost:8000**
- 管理側：**http://admin.localhost:8000**

1つのNginxがHost名で各appへ振り分けます。`*.localhost` はローカルPCを指すため、
同じPCのブラウザーからアクセスする場合に `hosts` の設定は不要です。
Vite は http://localhost:5173 で動作します（アプリの閲覧先ではありません）。
この作業環境では 5173 が使用中だったため、Git 管理対象外の
`docker/.env` で `VITE_PORT=5174` に設定しています。
Vite は両方のローカルオリジンを許可します。公開ポートはホストのループバックアドレスに限定しています。

## ファイルとデータの保存

- `src/` を `/app` にマウントし、Vue / TypeScript は Vite の HMR で更新します。
- PHP / Blade ファイルの変更時はページがリロードされます。
- Composer の `vendor` は専用ボリュームに分離します。
- npm の `node_modules` は `src/node_modules` をホストとコンテナで共有します。
  Vite 起動時の `npm ci` でインストールされ、ホスト側の VS Code も型定義を参照できます。
  npm のインストール操作はコンテナ側に統一し、ホストとの同時実行を避けてください。
- MariaDB のデータは `mariadb_data` の名前付きボリュームに保存します。
- 移行前のMySQLデータ用 `mysql_data` ボリュームはロールバック用に保持します。
- `.env`、旧SQLite（`src/database/database.sqlite`）、`storage/` はホストに保存します。
- `local/order.env` と `local/admin.env` はホスト側で管理し、Compose の `env_file` で
  各コンテナへ環境変数として渡します。設定ファイル自体はコンテナへマウントしません。
- `app-order` と `app-admin` で `storage` と `bootstrap/cache` の名前付きボリュームを分け、
  セッション・キャッシュ・ログ・設定キャッシュが混在しないようにします。
- 業務ファイル用の `shared_business_files` だけを両appの `/shared/business-files` にマウントします。
  Laravelでは `business_shared` ディスクとして利用できます。発注・管理双方からファイルを扱う
  実装では、ファイル取得・更新のたびに利用者の所属と対象業務データへの権限を検証してください。
  Nginxから直接公開せず、認可を行うLaravelの処理を通して配信します。
- Composer の `vendor` は2つのappで共有し、起動順序により同時インストールを避けます。
- SQLite ファイルは保持しますが、データを MariaDB に自動移行しません。
- SQLite と秘密情報を含む `.env` は Git 管理対象外です。
- Nginx・PHP-FPM・Vite は非 root ユーザーで動作します。
  MariaDB は公式イメージの初期化後、サーバープロセスを mysql ユーザーで実行します。

## 起動・停止・ログ

以下は `docker/` で実行します。

```bash
docker compose up --build -d
docker compose logs -f nginx app-order app-admin vite mariadb
docker compose down
```

`docker compose down` はMariaDB、SQLite、依存パッケージ、共有業務ファイルのデータを削除しません。
**`docker compose down --volumes` はMariaDB・移行前MySQLの全DBデータ、共有業務ファイル、
環境別storage、設定キャッシュ、`vendor` を削除します。**
共有する `src/node_modules` はこのコマンドでは削除されません。
SQLite の削除や `migrate:fresh` は既存データを失うため、必要な場合だけ実行してください。

## ポートを変更する

`docker/.env` の `APP_PORT`（既定 8000）と `VITE_PORT`（既定 5173）を変更し、
`docker compose up -d` で再作成してください。
Laravel の `APP_URL`、Vite の CORS、HMR ポートも Compose から連動して設定します。
`src/.env` の `APP_URL` は Docker 以外で実行する場合にも合わせて変更してください。

## 関連資料

- [本番サーバーへの配置](production.md)
- [構成概要](architecture.md)
- [MariaDBへの接続](database.md)
- [開発・検証手順](development.md)
- [資料一覧](../README.md)
