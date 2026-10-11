# データベース

## MySQLへの接続

MySQL 8.4 LTS を使用します。Docker 内の Laravel は以下の接続設定を使用します。

| 設定 | 値 |
| --- | --- |
| ホスト | `mysql` |
| ポート | `3306`（Docker 内部のみ） |
| データベース | `MYSQL_DATABASE`（既定 `ltbs`） |
| ユーザー | `MYSQL_USER`（既定 `ltbs`、root は使用しません） |
| パスワード | `docker/.env` の `MYSQL_PASSWORD` |
| 文字コード / 照合順序 | `utf8mb4` / `utf8mb4_unicode_ci` |

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

## 認証情報の変更

MySQL イメージの初期化用環境変数は、空のボリュームを初期化するときだけ反映されます。
既存の DB に対するパスワードやユーザー変更は SQL で行い、
`docker/.env` も合わせて更新してください。データ保持のためにボリュームを削除してはいけません。

## 永続化とSQLite

MySQL のデータは `mysql_data` の名前付きボリュームに保存します。
`docker compose down` では削除されません。
**`docker compose down --volumes` は MySQL の全データを削除します。**

旧SQLiteファイル（`src/database/database.sqlite`）は保持しています。
SQLite から MySQL へのデータの自動移行は行いません。
SQLite の削除や `migrate:fresh` も既存データを失うため、必要な場合だけ実行してください。

PHPUnit は Docker の MySQL 環境変数を上書きして SQLite のメモリDBを使用します。
テスト用接続が分離されていることも Feature テストで検証します。

## 関連資料

- [Docker環境のセットアップ](docker.md)
- [マイグレーション・検証手順](development.md)
- [資料一覧](../README.md)
