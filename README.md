# LTBS

Land Transport Booking System（陸送発注管理システム）のひな型です。
Laravel 13 + Vue 3 + TypeScript を使用し、画面データはAxiosでJSON APIから取得します。
スタイルは Sass（SCSS）と Bootstrap 5.3 を使用します。
認証、PWA、業務機能はまだ実装していません。

## ドキュメント

GitHub Copilot向けの技術構成・開発方針は
[Copilot instructions](.github/copilot-instructions.md) に記載しています。

| 資料                                                 | 内容                                                          |
| ---------------------------------------------------- | ------------------------------------------------------------- |
| [構成概要](doc/architecture.md)                      | ディレクトリ構成、Laravel / Vue / JSON API、Nginx / PHP-FPM   |
| [Docker環境](doc/docker.md)                          | 初回セットアップ、起動・停止、ポート、ファイル共有            |
| [本番配置](doc/production.md)                         | ホスト Nginx、Docker 上のアプリ、本番サーバー内の MariaDB    |
| [Deployer 実行環境](doc/deployer.md)                  | Docker 内の PHP での実行、SSH 接続設定例と確認手順          |
| [データベース](doc/database.md)                      | MariaDB接続、永続化、認証情報、業務テーブル設計案             |
| [開発ガイド](doc/development.md)                     | 開発・検証、ローカル環境、Sass / Bootstrap、Figma MCP連携    |
| [要件定義書（たたき台）](doc/要件定義書_たたき台.md) | 業務要件・機能の初期案                                        |
| [発注側画面・URL設計案](doc/order-screens.md)         | 発注側の画面ひな型、URL、画面遷移、実装順序                   |
| [受注・管理側画面・URL設計案](doc/admin-screens.md)   | 受注・管理側の画面ひな型、URL、画面遷移、実装順序             |
| [得意先の利用許可・初期管理者登録方針](doc/customer-onboarding.md) | 署名付き一時URL、メール案内、登録時の検証、保存情報の案 |
| [参考画面](doc/陸送発注.png)                         | 陸送発注画面の参考資料                                        |

開発環境の準備は [Docker環境](doc/docker.md) から始めてください。
起動後のアクセス先は **http://localhost:8000** です。

開発環境は [Docker環境](doc/docker.md)、本番サーバーへの配置は
[本番配置](doc/production.md) を参照してください。
