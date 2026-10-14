# 構成概要

## ディレクトリ構成

```text
.
├── docker/
│   ├── Dockerfile         # 開発・本番用のコンテナイメージ
│   ├── compose.yaml       # 開発用: Nginx・PHP-FPM・Vite・MariaDB
│   ├── compose.production.yaml # 本番用: Web Nginx・PHP-FPM
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
└── doc/                   # 開発手順・要件定義・参考資料
```

## アプリケーション

Laravel 13 + Inertia.js 3 + Vue 3 + TypeScript を使用します。
スタイルは Sass（SCSS）と Bootstrap 5.3 を使用します。

Laravel のルートから `Inertia::render()` で Vue ページを返す構成です。
独立した SPA 用 API サーバーや Vue Router は使用していません。

## 開発環境

Web サーバーは Nginx です。PHP 内蔵の開発用 Web サーバーは使用しません。
Nginx が静的ファイルを配信し、Laravel へのリクエストを
`app:9000` の PHP-FPM に FastCGI で渡します。
PHP-FPM のポートはホストに公開せず、Nginx のみを `APP_PORT` で公開します。
Nginx には `src/public/` だけを読み取り専用でマウントしています。

Vite はフロントエンドの開発用配信とホットリロードを担当します。
MariaDB は Docker 内部で Laravel から接続します。

## 本番環境

本番では実サーバー上の Nginx が TLS 終端とリバースプロキシを担当します。
転送先の `127.0.0.1:8080` は Docker 内の Web Nginx で、PHP リクエストを
Docker 内の Laravel/PHP-FPM に FastCGI で渡します。
PHP-FPM はホストにポート公開しません。MariaDB は Compose の外にある既存 DB を使います。

詳細な設定と配置手順は [本番配置](production.md) を参照してください。

## 関連資料

- [Docker環境](docker.md)
- [データベース](database.md)
- [開発ガイド](development.md)
- [資料一覧](../README.md)
