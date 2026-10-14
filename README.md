# LTBS

Land Transport Booking System（陸送発注管理システム）のひな型です。
Laravel 13 + Inertia.js 3 + Vue 3 + TypeScript を使用します。
スタイルは Sass（SCSS）と Bootstrap 5.3 を使用します。
認証、PWA、業務機能はまだ実装していません。

## ドキュメント

GitHub Copilot向けの技術構成・開発方針は
[Copilot instructions](.github/copilot-instructions.md) に記載しています。

| 資料                                                 | 内容                                                          |
| ---------------------------------------------------- | ------------------------------------------------------------- |
| [構成概要](doc/architecture.md)                      | ディレクトリ構成、Laravel / Inertia、Nginx / PHP-FPM          |
| [Docker環境](doc/docker.md)                          | 初回セットアップ、起動・停止、ポート、ファイル共有            |
| [本番配置](doc/production.md)                         | ホスト Nginx、Docker 上のアプリ、外部 MariaDB                 |
| [データベース](doc/database.md)                      | MariaDB接続、永続化、認証情報、業務テーブル設計案             |
| [開発ガイド](doc/development.md)                     | Artisan、依存パッケージ、検証、ローカル開発、Sass / Bootstrap |
| [要件定義書（たたき台）](doc/要件定義書_たたき台.md) | 業務要件・機能の初期案                                        |
| [参考画面](doc/陸送発注.png)                         | 陸送発注画面の参考資料                                        |

開発環境の準備は [Docker環境](doc/docker.md) から始めてください。
起動後のアクセス先は **http://localhost:8000** です。

開発環境は [Docker環境](doc/docker.md)、本番サーバーへの配置は
[本番配置](doc/production.md) を参照してください。
