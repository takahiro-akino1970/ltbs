# 構成概要

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
└── doc/                   # 開発手順・要件定義・参考資料
```

## アプリケーション

Laravel 13 + Inertia.js 3 + Vue 3 + TypeScript を使用します。
スタイルは Sass（SCSS）と Bootstrap 5.3 を使用します。

Laravel のルートから `Inertia::render()` で Vue ページを返す構成です。
独立した SPA 用 API サーバーや Vue Router は使用していません。

## Webサーバー

Web サーバーは Nginx です。PHP 内蔵の開発用 Web サーバーは使用しません。
Nginx が静的ファイルを配信し、Laravel へのリクエストを
`app:9000` の PHP-FPM に FastCGI で渡します。
PHP-FPM のポートはホストに公開せず、Nginx のみを `APP_PORT` で公開します。
Nginx には `src/public/` だけを読み取り専用でマウントしています。

Vite はフロントエンドの開発用配信とホットリロードを担当します。
MySQL は Docker 内部で Laravel から接続します。

## 関連資料

- [Docker環境](docker.md)
- [データベース](database.md)
- [開発ガイド](development.md)
- [資料一覧](../README.md)
