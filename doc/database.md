# データベース

## MariaDBへの接続

MariaDB 11.4 LTS を使用します。Docker 内の Laravel は以下の接続設定を使用します。

| 設定 | 値 |
| --- | --- |
| ホスト | `mariadb` |
| ポート | `3306`（Docker 内部のみ） |
| データベース | `MARIADB_DATABASE`（既定 `ltbs`） |
| ユーザー | `MARIADB_USER`（既定 `ltbs`、root は使用しません） |
| パスワード | `docker/.env` の `MARIADB_PASSWORD` |
| 文字コード / 照合順序 | `utf8mb4` / `utf8mb4_unicode_ci` |

Compose が Laravel に環境変数を渡すため、Docker では `src/.env` の
SQLite 設定より MariaDB 設定が優先されます。Laravelの `mariadb` 接続設定を使用し、
PHPの `pdo_mysql` 拡張を通じて接続します。
ローカル開発と PHPUnit のテストは
引き続き SQLite を使用できるよう `pdo_sqlite` も残しています。

MariaDB はホストに公開していません。CLI で接続する場合は `docker/` で実行します。
パスワードはプロンプトに `MARIADB_PASSWORD` の値を入力してください。

```bash
docker compose exec mariadb mysql -u ltbs -p ltbs
docker compose exec app php artisan migrate:status
```

ユーザー名 / DB 名を変更した場合は上記の `ltbs` を読み替えてください。
`mysql` はMariaDBクライアントの互換コマンド名です。MySQL製品のクライアントではありません。

## DDLの手動実行

`docker/sql/` はMariaDBコンテナ内の `/ddl` に読み取り専用でマウントされます。
SQLファイルはMariaDB起動時には自動実行されません。DDLを実行する場合は、MariaDBコンテナが
起動している状態で、次のようにMariaDBクライアントへ接続してから `SOURCE` を実行します。

```bash
docker compose exec mariadb mysql -u ltbs -p ltbs
```

```sql
SOURCE /ddl/001_booking_schema.sql;
```

現在のDDL案は [001_booking_schema.sql](../docker/sql/001_booking_schema.sql) にあります。
テーブル作成用DDLのため、同じファイルを再実行すると既存テーブルがある箇所でエラーになります。
適用前に対象DBと既存データを確認し、必要に応じてバックアップしてください。

## 認証情報の変更

MariaDB イメージの初期化用環境変数は、空のボリュームを初期化するときだけ反映されます。
既存の DB に対するパスワードやユーザー変更は SQL で行い、
`docker/.env` も合わせて更新してください。データ保持のためにボリュームを削除してはいけません。

## 永続化とSQLite

MariaDB のデータは `mariadb_data` の名前付きボリュームに保存します。
`docker compose down` では削除されません。
**`docker compose down --volumes` は MariaDB の全データと移行前MySQLの全データを削除します。**
旧MySQLデータ用の `mysql_data` ボリュームは、移行後のロールバックに備えて保持しています。
MariaDBから旧MySQLへ切り戻す場合は、MariaDB側の更新内容を別途論理移行してください。

旧SQLiteファイル（`src/database/database.sqlite`）は保持しています。
SQLite から MariaDB へのデータの自動移行は行いません。
SQLite の削除や `migrate:fresh` も既存データを失うため、必要な場合だけ実行してください。

MariaDBへの切替時はMySQLから論理バックアップを取り、新しい `mariadb_data` に復元しています。
旧MySQLコンテナは削除しましたが、元データの `mysql_data` ボリュームは保持しています。

PHPUnit は Docker の DB 環境変数を上書きして SQLite のメモリDBを使用します。
テスト用接続が分離されていることも Feature テストで検証します。

## 業務テーブル設計案

この節は陸送発注管理機能の初期設計案です。DDL は MariaDB 11.4 LTS、InnoDB、
`utf8mb4_unicode_ci` を前提としています。日時は UTC で `DATETIME(3)` に保存し、
画面表示時に利用者のタイムゾーンへ変換します。業務ルールが未確定の項目は、
運用確定後に制約や選択肢を調整してください。

### 設計方針

- 新規作成するテーブルには原則としてNULL許容の`deleted_at`を追加し、論理削除を前提に設計します。明確に不要な理由がある場合のみ省略し、理由を記録します。以下の既存DDL案についても、実装時にこの方針を適用します。
- 論理削除では削除済みデータの参照・認証対象からの除外、一意制約、復元、初期登録済み判定への影響を確認します。`deleted_at`の追加だけで対応完了とはしません。
- 発注側ユーザーは必ず1つの得意先に所属します。利用者と陸送会社の直接の紐付けは行いません。
- 発注側と受注・システム管理側は別アプリ環境・別認証入口とします。このDDL案の `users` は得意先所属ユーザーを前提としており、陸送会社担当者のアカウントを表現できません。認証実装前にユーザー種別・組織所属・ロール、および発注者・受注担当者の外部キー設計を見直します。
- 両アプリ環境は発注・受注データを共通DBで利用します。アプリ環境ごとの認証分離だけではデータアクセスは分離されないため、アプリケーション側で所属・権限を検証します。
- 得意先と陸送会社の対応を `customer_carriers` で管理します。将来、複数社が受注対象となる構成に対応します。
- 発注に打診した陸送会社を `order_carrier_targets` に記録します。複数社への同時打診にも対応できます。
- 発注の出発地・到着地は、地点マスタIDと発注時点の住所スナップショットを併せて保持します。
- 車種は `vehicle_types` から選択し、発注時点の車種名もスナップショットとして保持します。
- 画像本体はオブジェクトストレージに保存し、DBには保存キー等のメタデータを記録します。
- ステータス・ロール等のコード値は大文字の英数字とアンダースコアを使用します。

### DDL

以下は主要テーブルの初期案です。マイグレーションでは、プロジェクトの命名規約や
Laravelの標準カラムに合わせて調整してください。

`customers` と `vehicle_types` は別スキーマにある既存テーブルを参照します。
Docker内の `db_stsystem` データベースに作成済みのため、このDDLでは作成しません。
論理名 `customers` は `db_stsystem.customers`、論理名 `vehicle_types` は
`db_stsystem.carmodels` に対応します。
以下の `COMMENT` はテーブル名・カラム名の論理名を示します。既存マスタの定義やコメントは
このDDLでは変更しません。

```sql
-- 陸送会社
CREATE TABLE carriers (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '陸送会社ID',
    carrier_code VARCHAR(50) NOT NULL COMMENT '陸送会社コード',
    name VARCHAR(200) NOT NULL COMMENT '陸送会社名',
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT '状態',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3) COMMENT '更新日時',
    deleted_at DATETIME(3) NULL COMMENT '削除日時',
    PRIMARY KEY (id),
    UNIQUE KEY uq_carriers_code (carrier_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='陸送会社';

-- 得意先・陸送会社対応
CREATE TABLE customer_carriers (
    customer_id BIGINT UNSIGNED NOT NULL COMMENT '得意先ID',
    carrier_id BIGINT UNSIGNED NOT NULL COMMENT '陸送会社ID',
    is_default TINYINT(1) NOT NULL DEFAULT 0 COMMENT '既定陸送会社フラグ',
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT '状態',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (customer_id, carrier_id),
    CONSTRAINT fk_customer_carriers_customer
        FOREIGN KEY (customer_id) REFERENCES db_stsystem.customers (id),
    CONSTRAINT fk_customer_carriers_carrier
        FOREIGN KEY (carrier_id) REFERENCES carriers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='得意先・陸送会社対応';

-- 利用者
CREATE TABLE users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '利用者ID',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT '所属得意先ID',
    login_id VARCHAR(100) NOT NULL COMMENT 'ログインID',
    password_hash VARCHAR(255) NOT NULL COMMENT 'パスワードハッシュ',
    name VARCHAR(200) NOT NULL COMMENT '利用者名',
    email VARCHAR(254) NULL COMMENT 'メールアドレス',
    tel VARCHAR(30) NULL COMMENT '電話番号',
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT '状態',
    last_login_at DATETIME(3) NULL COMMENT '最終ログイン日時',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3) COMMENT '更新日時',
    deleted_at DATETIME(3) NULL COMMENT '削除日時',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_login_id (login_id),
    KEY ix_users_customer_status (customer_id, status),
    CONSTRAINT fk_users_customer
        FOREIGN KEY (customer_id) REFERENCES db_stsystem.customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='利用者';

-- ロール
CREATE TABLE roles (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ロールID',
    role_code VARCHAR(50) NOT NULL COMMENT 'ロールコード',
    name VARCHAR(100) NOT NULL COMMENT 'ロール名',
    organization_type VARCHAR(20) NOT NULL COMMENT '組織種別',
    PRIMARY KEY (id),
    UNIQUE KEY uq_roles_code (role_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ロール';

-- 利用者・ロール対応
CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL COMMENT '利用者ID',
    role_id BIGINT UNSIGNED NOT NULL COMMENT 'ロールID',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user
        FOREIGN KEY (user_id) REFERENCES users (id),
    CONSTRAINT fk_user_roles_role
        FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='利用者・ロール対応';

-- 発注ステータス定義
CREATE TABLE order_statuses (
    code VARCHAR(30) NOT NULL COMMENT 'ステータスコード',
    name VARCHAR(50) NOT NULL COMMENT 'ステータス名',
    sort_order INT NOT NULL DEFAULT 0 COMMENT '表示順',
    is_terminal TINYINT(1) NOT NULL DEFAULT 0 COMMENT '終端ステータスフラグ',
    PRIMARY KEY (code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='発注ステータス定義';

-- 陸送発注
CREATE TABLE transport_orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '発注ID',
    order_no VARCHAR(32) NOT NULL COMMENT '発注番号',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT '得意先ID',
    requester_user_id BIGINT UNSIGNED NOT NULL COMMENT '発注者利用者ID',
    vehicle_type_id BIGINT UNSIGNED NOT NULL COMMENT '車種ID',
    vehicle_type_name_snapshot VARCHAR(100) NOT NULL COMMENT '発注時車種名',
    status_code VARCHAR(30) NOT NULL COMMENT '発注ステータスコード',
    chassis_number VARCHAR(100) NULL COMMENT '車台番号',
    vehicle_number VARCHAR(30) NULL COMMENT '車両番号',
    requested_at DATETIME(3) NOT NULL COMMENT '発注日時',
    departure_scheduled_at DATETIME(3) NULL COMMENT '出発予定日時',
    arrival_scheduled_at DATETIME(3) NULL COMMENT '到着予定日時',
    actual_departed_at DATETIME(3) NULL COMMENT '実出発日時',
    actual_arrived_at DATETIME(3) NULL COMMENT '実到着日時',
    remarks TEXT NULL COMMENT '備考',
    accepted_carrier_id BIGINT UNSIGNED NULL COMMENT '受注陸送会社ID',
    accepted_by BIGINT UNSIGNED NULL COMMENT '受注処理利用者ID',
    accepted_at DATETIME(3) NULL COMMENT '受注日時',
    completed_at DATETIME(3) NULL COMMENT '完了日時',
    canceled_by BIGINT UNSIGNED NULL COMMENT 'キャンセル処理利用者ID',
    canceled_at DATETIME(3) NULL COMMENT 'キャンセル日時',
    cancel_reason TEXT NULL COMMENT 'キャンセル理由',
    route_distance_m INT UNSIGNED NULL COMMENT 'ルート距離（メートル）',
    route_duration_sec INT UNSIGNED NULL COMMENT 'ルート所要時間（秒）',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3) COMMENT '更新日時',
    PRIMARY KEY (id),
    UNIQUE KEY uq_transport_orders_order_no (order_no),
    UNIQUE KEY uq_transport_orders_id_customer (id, customer_id),
    KEY ix_orders_customer_created (customer_id, created_at),
    KEY ix_orders_status_created (status_code, created_at),
    KEY ix_orders_requester_created (requester_user_id, created_at),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES db_stsystem.customers (id),
    CONSTRAINT fk_orders_requester
        FOREIGN KEY (requester_user_id) REFERENCES users (id),
    CONSTRAINT fk_orders_vehicle_type
        FOREIGN KEY (vehicle_type_id) REFERENCES db_stsystem.carmodels (id),
    CONSTRAINT fk_orders_status
        FOREIGN KEY (status_code) REFERENCES order_statuses (code),
    CONSTRAINT fk_orders_accepted_by
        FOREIGN KEY (accepted_by) REFERENCES users (id),
    CONSTRAINT fk_orders_canceled_by
        FOREIGN KEY (canceled_by) REFERENCES users (id),
    CONSTRAINT fk_orders_accepted_customer_carrier
        FOREIGN KEY (customer_id, accepted_carrier_id)
        REFERENCES customer_carriers (customer_id, carrier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='陸送発注';

-- 得意先地点マスタ
CREATE TABLE customer_locations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '地点ID',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT '得意先ID',
    location_type VARCHAR(20) NOT NULL COMMENT '地点種別',
    name VARCHAR(200) NOT NULL COMMENT '地点名',
    postal_code VARCHAR(10) NULL COMMENT '郵便番号',
    prefecture VARCHAR(20) NULL COMMENT '都道府県',
    city VARCHAR(100) NULL COMMENT '市区町村',
    address_line VARCHAR(300) NULL COMMENT '住所詳細',
    latitude DECIMAL(10,7) NULL COMMENT '緯度',
    longitude DECIMAL(10,7) NULL COMMENT '経度',
    place_id VARCHAR(255) NULL COMMENT '地図サービス地点ID',
    contact_name VARCHAR(200) NULL COMMENT '連絡先担当者名',
    contact_tel VARCHAR(30) NULL COMMENT '連絡先電話番号',
    is_active TINYINT(1) NOT NULL DEFAULT 1 COMMENT '有効フラグ',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3) COMMENT '更新日時',
    PRIMARY KEY (id),
    KEY ix_customer_locations_customer_type
        (customer_id, location_type, is_active),
    CONSTRAINT fk_customer_locations_customer
        FOREIGN KEY (customer_id) REFERENCES db_stsystem.customers (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='得意先地点マスタ';

-- 発注地点スナップショット
CREATE TABLE order_points (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '発注地点ID',
    order_id BIGINT UNSIGNED NOT NULL COMMENT '発注ID',
    point_type VARCHAR(20) NOT NULL COMMENT '地点種別',
    customer_location_id BIGINT UNSIGNED NULL COMMENT '参照得意先地点ID',
    name VARCHAR(200) NOT NULL COMMENT '地点名',
    postal_code VARCHAR(10) NULL COMMENT '郵便番号',
    prefecture VARCHAR(20) NULL COMMENT '都道府県',
    city VARCHAR(100) NULL COMMENT '市区町村',
    address_line VARCHAR(300) NULL COMMENT '住所詳細',
    latitude DECIMAL(10,7) NULL COMMENT '緯度',
    longitude DECIMAL(10,7) NULL COMMENT '経度',
    place_id VARCHAR(255) NULL COMMENT '地図サービス地点ID',
    provider_address TEXT NULL COMMENT '地図サービス住所',
    source VARCHAR(20) NOT NULL COMMENT '地点情報の取得元',
    is_manually_edited TINYINT(1) NOT NULL DEFAULT 0 COMMENT '手動編集フラグ',
    confirmed_at DATETIME(3) NULL COMMENT '地点確認日時',
    contact_name VARCHAR(200) NULL COMMENT '連絡先担当者名',
    contact_tel VARCHAR(30) NULL COMMENT '連絡先電話番号',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    updated_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ON UPDATE CURRENT_TIMESTAMP(3) COMMENT '更新日時',
    PRIMARY KEY (id),
    UNIQUE KEY uq_order_points_order_type (order_id, point_type),
    CONSTRAINT fk_order_points_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_points_location
        FOREIGN KEY (customer_location_id) REFERENCES customer_locations (id),
    CONSTRAINT chk_order_points_type
        CHECK (point_type IN ('PICKUP', 'DELIVERY')),
    CONSTRAINT chk_order_points_source
        CHECK (source IN ('MAP', 'HISTORY', 'MANUAL'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='発注地点スナップショット';

-- 発注打診先陸送会社
CREATE TABLE order_carrier_targets (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '打診先ID',
    order_id BIGINT UNSIGNED NOT NULL COMMENT '発注ID',
    customer_id BIGINT UNSIGNED NOT NULL COMMENT '得意先ID',
    carrier_id BIGINT UNSIGNED NOT NULL COMMENT '打診先陸送会社ID',
    response_status VARCHAR(20) NOT NULL DEFAULT 'OFFERED' COMMENT '応答状態',
    offered_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '打診日時',
    responded_at DATETIME(3) NULL COMMENT '応答日時',
    responded_by BIGINT UNSIGNED NULL COMMENT '応答利用者ID',
    PRIMARY KEY (id),
    UNIQUE KEY uq_order_carrier_targets_pair (order_id, carrier_id),
    KEY ix_order_carrier_targets_queue
        (carrier_id, response_status, offered_at),
    CONSTRAINT fk_order_carrier_targets_order_customer
        FOREIGN KEY (order_id, customer_id)
        REFERENCES transport_orders (id, customer_id),
    CONSTRAINT fk_order_carrier_targets_customer_carrier
        FOREIGN KEY (customer_id, carrier_id)
        REFERENCES customer_carriers (customer_id, carrier_id),
    CONSTRAINT fk_order_carrier_targets_responded_by
        FOREIGN KEY (responded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='発注打診先陸送会社';

-- 発注ステータス変更履歴
CREATE TABLE order_status_histories (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'ステータス履歴ID',
    order_id BIGINT UNSIGNED NOT NULL COMMENT '発注ID',
    from_status_code VARCHAR(30) NULL COMMENT '変更前ステータスコード',
    to_status_code VARCHAR(30) NOT NULL COMMENT '変更後ステータスコード',
    changed_by BIGINT UNSIGNED NOT NULL COMMENT '変更利用者ID',
    changed_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '変更日時',
    comment TEXT NULL COMMENT '変更コメント',
    PRIMARY KEY (id),
    KEY ix_order_status_histories_order_time (order_id, changed_at),
    CONSTRAINT fk_order_status_histories_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_status_histories_from
        FOREIGN KEY (from_status_code) REFERENCES order_statuses (code),
    CONSTRAINT fk_order_status_histories_to
        FOREIGN KEY (to_status_code) REFERENCES order_statuses (code),
    CONSTRAINT fk_order_status_histories_user
        FOREIGN KEY (changed_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='発注ステータス変更履歴';

-- 発注メッセージ
CREATE TABLE order_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'メッセージID',
    order_id BIGINT UNSIGNED NOT NULL COMMENT '発注ID',
    sender_user_id BIGINT UNSIGNED NOT NULL COMMENT '送信利用者ID',
    message_type VARCHAR(30) NOT NULL DEFAULT 'MESSAGE' COMMENT 'メッセージ種別',
    body TEXT NOT NULL COMMENT '本文',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (id),
    KEY ix_order_messages_order_created (order_id, created_at),
    CONSTRAINT fk_order_messages_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_messages_sender
        FOREIGN KEY (sender_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='発注メッセージ';

-- 発注添付ファイル
CREATE TABLE order_attachments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '添付ファイルID',
    order_id BIGINT UNSIGNED NOT NULL COMMENT '発注ID',
    storage_key VARCHAR(500) NOT NULL COMMENT 'ストレージ保存キー',
    original_filename VARCHAR(255) NOT NULL COMMENT '元ファイル名',
    content_type VARCHAR(100) NOT NULL COMMENT 'コンテンツタイプ',
    size_bytes BIGINT UNSIGNED NOT NULL COMMENT 'ファイルサイズ（バイト）',
    uploaded_by BIGINT UNSIGNED NOT NULL COMMENT 'アップロード利用者ID',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    deleted_at DATETIME(3) NULL COMMENT '削除日時',
    PRIMARY KEY (id),
    KEY ix_order_attachments_order (order_id),
    CONSTRAINT fk_order_attachments_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_order_attachments_user
        FOREIGN KEY (uploaded_by) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='発注添付ファイル';

-- OCR結果
CREATE TABLE ocr_results (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'OCR結果ID',
    attachment_id BIGINT UNSIGNED NOT NULL COMMENT '添付ファイルID',
    candidate_vehicle_number VARCHAR(30) NULL COMMENT 'OCR候補車両番号',
    confidence DECIMAL(5,4) NULL COMMENT 'OCR信頼度',
    result_status VARCHAR(20) NOT NULL COMMENT '確認状態',
    confirmed_vehicle_number VARCHAR(30) NULL COMMENT '確定車両番号',
    confirmed_by BIGINT UNSIGNED NULL COMMENT '確認利用者ID',
    confirmed_at DATETIME(3) NULL COMMENT '確認日時',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (id),
    KEY ix_ocr_results_attachment (attachment_id, created_at),
    CONSTRAINT fk_ocr_results_attachment
        FOREIGN KEY (attachment_id) REFERENCES order_attachments (id),
    CONSTRAINT fk_ocr_results_confirmed_by
        FOREIGN KEY (confirmed_by) REFERENCES users (id),
    CONSTRAINT chk_ocr_results_confidence
        CHECK (confidence IS NULL OR confidence BETWEEN 0 AND 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='OCR結果';

-- 通知イベント
CREATE TABLE notification_events (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通知イベントID',
    event_type VARCHAR(50) NOT NULL COMMENT 'イベント種別',
    order_id BIGINT UNSIGNED NULL COMMENT '発注ID',
    actor_user_id BIGINT UNSIGNED NULL COMMENT '実行利用者ID',
    payload JSON NULL COMMENT 'イベントペイロード',
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING' COMMENT '処理状態',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    processed_at DATETIME(3) NULL COMMENT '処理日時',
    PRIMARY KEY (id),
    KEY ix_notification_events_queue (status, created_at),
    CONSTRAINT fk_notification_events_order
        FOREIGN KEY (order_id) REFERENCES transport_orders (id),
    CONSTRAINT fk_notification_events_actor
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='通知イベント';

-- 利用者通知
CREATE TABLE user_notifications (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '利用者通知ID',
    event_id BIGINT UNSIGNED NOT NULL COMMENT '通知イベントID',
    recipient_user_id BIGINT UNSIGNED NOT NULL COMMENT '受信利用者ID',
    title VARCHAR(200) NOT NULL COMMENT '通知タイトル',
    body TEXT NOT NULL COMMENT '通知本文',
    read_at DATETIME(3) NULL COMMENT '既読日時',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (id),
    KEY ix_user_notifications_inbox
        (recipient_user_id, read_at, created_at),
    CONSTRAINT fk_user_notifications_event
        FOREIGN KEY (event_id) REFERENCES notification_events (id),
    CONSTRAINT fk_user_notifications_recipient
        FOREIGN KEY (recipient_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='利用者通知';

-- Web Push購読情報
CREATE TABLE push_subscriptions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '購読ID',
    user_id BIGINT UNSIGNED NOT NULL COMMENT '利用者ID',
    endpoint_hash BINARY(32) NOT NULL COMMENT 'エンドポイントハッシュ',
    endpoint_ciphertext TEXT NOT NULL COMMENT '暗号化エンドポイント',
    p256dh_ciphertext TEXT NOT NULL COMMENT '暗号化P-256公開鍵',
    auth_ciphertext TEXT NOT NULL COMMENT '暗号化認証シークレット',
    browser_info VARCHAR(500) NULL COMMENT 'ブラウザー情報',
    status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE' COMMENT '購読状態',
    subscribed_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '購読日時',
    last_used_at DATETIME(3) NULL COMMENT '最終利用日時',
    unsubscribed_at DATETIME(3) NULL COMMENT '購読解除日時',
    PRIMARY KEY (id),
    UNIQUE KEY uq_push_subscriptions_endpoint_hash (endpoint_hash),
    KEY ix_push_subscriptions_user_status (user_id, status),
    CONSTRAINT fk_push_subscriptions_user
        FOREIGN KEY (user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Web Push購読情報';

-- 通知配信結果
CREATE TABLE notification_deliveries (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '通知配信ID',
    notification_id BIGINT UNSIGNED NOT NULL COMMENT '利用者通知ID',
    channel VARCHAR(20) NOT NULL COMMENT '配信チャネル',
    push_subscription_id BIGINT UNSIGNED NULL COMMENT 'Push購読ID',
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING' COMMENT '配信状態',
    attempt_count INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '送信試行回数',
    last_attempt_at DATETIME(3) NULL COMMENT '最終送信試行日時',
    failure_reason TEXT NULL COMMENT '失敗理由',
    delivered_at DATETIME(3) NULL COMMENT '配信完了日時',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (id),
    KEY ix_notification_deliveries_retry (status, last_attempt_at),
    CONSTRAINT fk_notification_deliveries_notification
        FOREIGN KEY (notification_id) REFERENCES user_notifications (id),
    CONSTRAINT fk_notification_deliveries_subscription
        FOREIGN KEY (push_subscription_id) REFERENCES push_subscriptions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='通知配信結果';

-- 監査ログ
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '監査ログID',
    actor_user_id BIGINT UNSIGNED NULL COMMENT '実行利用者ID',
    action VARCHAR(50) NOT NULL COMMENT '操作種別',
    entity_type VARCHAR(100) NOT NULL COMMENT '対象種別',
    entity_id VARCHAR(64) NOT NULL COMMENT '対象ID',
    before_data JSON NULL COMMENT '変更前データ',
    after_data JSON NULL COMMENT '変更後データ',
    created_at DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3) COMMENT '作成日時',
    PRIMARY KEY (id),
    KEY ix_audit_logs_entity (entity_type, entity_id, created_at),
    KEY ix_audit_logs_actor (actor_user_id, created_at),
    CONSTRAINT fk_audit_logs_actor
        FOREIGN KEY (actor_user_id) REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='監査ログ';
```

### テーブルの役割

| テーブル | 用途 |
| --- | --- |
| `customers`（`db_stsystem.customers`） / `carriers` | 既存の得意先マスタ / 陸送会社の基本情報 |
| `customer_carriers` | 発注を受け付ける得意先・陸送会社の組み合わせ |
| `users` / `roles` / `user_roles` | ログインユーザー、所属組織、ロール |
| `vehicle_types`（`db_stsystem.carmodels`） | 既存の車種候補 |
| `transport_orders` | 発注の基本情報、受注・完了・キャンセル情報 |
| `customer_locations` / `order_points` | 得意先地点マスタと発注時点の地点スナップショット |
| `order_carrier_targets` | 発注を打診した陸送会社と各社の応答 |
| `order_statuses` / `order_status_histories` | ステータス定義と変更履歴 |
| `order_messages` | 発注に関する連絡履歴 |
| `order_attachments` / `ocr_results` | 車両番号画像のメタデータとOCR候補・確認結果 |
| `notification_events` | 業務イベントと非同期通知処理のOutbox |
| `user_notifications` | 利用者ごとの画面内通知・既読状態 |
| `push_subscriptions` | Web Pushの購読端末。endpointと鍵は暗号化して保存 |
| `notification_deliveries` | Push・メール等の送信結果と再送情報 |
| `audit_logs` | 重要操作の実行者、対象、変更前後 |

### 制約・アプリケーション側の処理

- 利用者は `customer_id` で所属得意先を特定します。利用者と陸送会社の直接の紐付けは行いません。
- `order_carrier_targets` の複合外部キーで、発注の得意先に対応する陸送会社だけを打診対象にできます。
- 受注処理では発注行をトランザクション内でロックし、ステータスを検証してから受注者・受注陸送会社・受注日時を保存します。これにより複数社の同時受注を防ぎます。
- `customer_carriers.is_default` の既定会社を1社に限定する制約は通常のユニーク制約だけでは表現しにくいため、設定更新をトランザクションで行いアプリケーション側で保証します。
- ステータス遷移、キャンセル条件、発注番号の採番規則は業務確定後に定義します。発注番号は一意制約を付け、採番処理はトランザクション等で重複を防止します。
- 利用者が操作できる発注は所属得意先で必ず絞り込みます。
- Push購読情報、パスワード、秘密鍵等をログや監査JSONに平文で記録しないでください。画像・通知履歴の保持期間と削除方針も別途定義します。

## 関連資料

- [Docker環境のセットアップ](docker.md)
- [マイグレーション・検証手順](development.md)
- [資料一覧](../README.md)
