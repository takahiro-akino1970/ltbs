# 本番サーバーへの配置

本番では、実サーバー上の Nginx が TLS 終端とリバースプロキシを担当し、
Laravel/PHP-FPM とアプリ配信用 Nginx を Docker Compose で起動します。
MariaDB は Compose に含めず、既存の外部データベースを利用します。
開発用の [compose.yaml](../docker/compose.yaml) とは別の構成です。

```text
ブラウザー
  └─ HTTPS → サーバー上の Nginx
               └─ HTTP → 127.0.0.1:8080 (Docker の web Nginx)
                            └─ FastCGI → app:9000 (PHP-FPM / Laravel)
                                           └─ TCP → 外部 MariaDB
```

## 前提とセキュリティ

- 実サーバーに Docker Engine、Docker Compose v2、Nginx がインストール済みであること。
- ドメインと TLS 証明書はホスト側 Nginx に設定すること。
- 外部 DB 側のファイアウォールでは、このサーバーからの接続だけを許可すること。
- DB ポートや PHP-FPM ポートは Docker のホストポートとして公開しません。
  Web コンテナの公開先も `127.0.0.1:8080` に限定します。
- `docker/production.env` には本番用の秘密情報を設定し、Git にコミットしないでください。
- `APP_KEY` は一度設定した後、再生成・変更しないでください。既存の暗号化データに影響します。

## 初回配置

プロジェクトをサーバーに配置し、プロジェクトルートで実行します。

```bash
cp docker/production.env.example docker/production.env
```

`docker/production.env` の `APP_URL` を実際の HTTPS URL に、
`DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` を
外部 MariaDB の接続情報に変更します。`APP_DEBUG=false` は本番で有効にしないでください。
DBユーザーには必要なデータベースだけへの権限を付与してください。

アプリと静的ファイル配信用のイメージをビルドします。

```bash
docker compose -f docker/compose.production.yaml build
```

APP_KEY を生成し、表示された値を `docker/production.env` の `APP_KEY` に設定します。

```bash
docker compose -f docker/compose.production.yaml run --rm app php artisan key:generate --show
```

外部 DB への接続を確認した上で、マイグレーションを実行します。
本番データに変更を加えるため、実行前に DB のバックアップを取得してください。

```bash
docker compose -f docker/compose.production.yaml run --rm app php artisan migrate --force
docker compose -f docker/compose.production.yaml up -d
```

## ホスト Nginx の設定

TLS 証明書を設定済みのドメイン用 `server` ブロック内に、
[host-proxy-location.conf.example](../docker/nginx/host-proxy-location.conf.example)
の `location /` を配置します。例:

```nginx
server {
    listen 443 ssl;
    server_name example.com;

    # ssl_certificate と ssl_certificate_key はサーバーの証明書に合わせて設定

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $remote_addr;
        proxy_set_header X-Forwarded-Host $host;
        proxy_set_header X-Forwarded-Port $server_port;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_read_timeout 60s;
    }
}
```

ドメイン名を `APP_URL` と `server_name` の両方に合わせます。
設定を反映する前に構文を確認してください。

```bash
sudo nginx -t
sudo systemctl reload nginx
```

アプリ配信用 Nginx はコンテナ内で静的ファイルを配信し、PHP リクエストを
PHP-FPM に渡します。Laravel は転送された HTTPS / クライアント情報を解釈するため、
本番アプリの HTTP ポートや PHP-FPM ポートを外部に公開しないでください。

## 更新

変更をサーバーに配置した後、新しいイメージをビルドし、必要なマイグレーションを
確認・実行してからサービスを再作成します。DBの変更前にはバックアップを取得します。

```bash
docker compose -f docker/compose.production.yaml build
docker compose -f docker/compose.production.yaml run --rm app php artisan migrate --force
docker compose -f docker/compose.production.yaml up -d
docker compose -f docker/compose.production.yaml logs -f app web
```

Laravel のログは Docker の標準エラー出力に送られます。
ユーザーアップロードなどを `storage/` に保存する場合は `app_storage` ボリュームに残ります。
DB とこのボリュームは、サーバー外へのバックアップも別途設定してください。
`docker compose down --volumes` は永続ボリュームを削除するため、本番では実行しないでください。

## 関連資料

- [Docker開発環境](docker.md)
- [構成概要](architecture.md)
- [データベース](database.md)
