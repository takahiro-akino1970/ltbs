# 受注・システム管理側画面・URL設計案

## 1. 文書の位置付け

本書は、受注・システム管理側アプリケーションで作成する画面ひな型とURLの初期案を整理したものです。
[要件定義書（たたき台）](./要件定義書_たたき台.md)をもとにしていますが、
認証方式、権限、業務ステータス、入力項目、外部サービス連携は未確定です。

初期の画面ひな型では、画面遷移、レスポンシブレイアウト、入力項目、各状態の表示を確認し、
DB登録、認証・認可、Push通知などの実処理は実装しません。

## 2. URL方針

受注・システム管理側は専用ドメインで提供するため、パスに `/admin` は付けません。

```text
開発環境: http://admin.localhost:${APP_PORT}
既定値:   http://admin.localhost:8000
本番例:   https://admin.example.jp
```

開発環境のポートは `docker/.env` の `APP_PORT` に従います。
発注ID、得意先ID、ユーザーIDなどの形式は未確定のため、本書では
`{order}`、`{customer}`、`{user}` のように表記します。

## 3. 共通レイアウト

認証後の画面は、次の共通レイアウトを使用する想定です。

- ヘッダー
  - ロゴ・アプリケーション名
  - 発注番号、車両番号、得意先の横断検索
  - 未読通知
  - ヘルプ
  - アカウントメニュー
- デスクトップ用サイドメニュー
- スマートフォン用下部ナビゲーション
- パンくずリスト
- 処理成功・失敗・入力エラーの通知領域
- メインコンテンツ

サイドメニュー案は次のとおりです。

```text
ダッシュボード       /
発注管理             /orders
輸送進捗             /operations
通知・連絡           /notifications
得意先管理           /customers
ユーザー管理         /users
ロール管理           /roles
地点管理             /locations
マスタ管理           /masters
監査ログ             /audit-logs
システム設定         /settings
```

スマートフォン用下部ナビゲーションは、業務頻度を考慮して次の4項目に絞ります。

```text
ホーム / 発注管理 / 輸送進捗 / 通知
```

## 4. 共通・認証画面

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/login` | `admin/auth/Login.vue` | 管理側専用ログイン |
| 中 | GET | `/forgot-password` | `admin/auth/ForgotPassword.vue` | パスワード再設定メールの送信 |
| 中 | GET | `/reset-password/{token}` | `admin/auth/ResetPassword.vue` | 新しいパスワードの設定 |
| 高 | GET | `/` | `admin/Dashboard.vue` | 受注・輸送状況の集計 |
| 低 | GET | `/help` | `admin/Help.vue` | 操作案内、問い合わせ先、FAQ |
| 低 | GET | `/forbidden` | `admin/errors/Forbidden.vue` | 権限不足表示 |
| 低 | GET | `/not-found` | `admin/errors/NotFound.vue` | ページが見つからない場合の表示 |

認証が実装されるまでは、ログイン関連画面は表示確認用モックとし、
業務画面には直接アクセスできる状態とします。

## 5. ダッシュボード

### `GET /`

受注管理業務の開始地点として、次の内容を表示します。

- 対応が必要な新規発注
- ステータス別件数
  - 新規発注
  - 仮発注
  - 受注済み
  - 輸送中
  - 完了
  - キャンセル
- 本日引取予定
- 本日納車予定
- 期限超過・遅延の可能性がある発注
- 最近更新された発注
- 未読の連絡・通知
- 担当者別の対応件数

ステータス名、集計対象、遅延判定条件は業務確認後に確定します。

## 6. 発注管理

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 最優先 | GET | `/orders` | `admin/orders/Index.vue` | 複数得意先を横断した発注一覧 |
| 最優先 | GET | `/orders/{order}` | `admin/orders/Show.vue` | 発注詳細と受注操作 |
| 高 | GET | `/orders/{order}?tab=vehicle` | `admin/orders/Show.vue` | 車両情報 |
| 高 | GET | `/orders/{order}?tab=route` | `admin/orders/Show.vue` | 引取先、納車先、ルート |
| 高 | GET | `/orders/{order}?tab=messages` | `admin/orders/Show.vue` | 発注者との連絡履歴 |
| 高 | GET | `/orders/{order}?tab=history` | `admin/orders/Show.vue` | ステータス変更・操作履歴 |

発注一覧では、次の検索・表示項目を用意します。

- フリーワード
- 発注番号・発注ID
- 車両番号・車台番号
- 得意先
- 発注者
- ステータス
- 発注日
- 引取予定日
- 納車予定日
- 引取先・納車先
- 受注担当者
- 未対応のみ
- 並び順
- 無限スクロール

検索条件はURLのクエリーパラメーターへ反映し、
再読み込みやURL共有時にも条件を維持できる構成を想定します。
一覧データは同一LaravelアプリケーションのJSON APIからカーソルページネーションで取得し、
`IntersectionObserver` を使って追加表示します。共通の実装方針は
[構成概要](architecture.md#一覧テーブルと無限スクロール)に従います。

## 7. 発注詳細・受注操作

### `GET /orders/{order}`

確認専用画面と操作画面を細かく分割せず、発注内容を確認しながら同じ画面で受注、
ステータス更新、発注者への連絡を行える構成とします。

デスクトップでは、発注内容と操作パネルを横並びにします。

```text
┌──────────────────────────────────────────────────────────────┐
│ ヘッダー                                                     │
├───────────────────────────────────┬──────────────────────────┤
│ 発注内容                          │ 受注・進捗操作           │
│                                   │                          │
│ 得意先・発注者                    │ 現在のステータス         │
│ 車両情報                          │ 担当者                   │
│ 引取先・納車先                    │ 受注する                 │
│ 希望日時                          │ ステータス更新           │
│ 備考・画像                        │ 発注者へ連絡             │
│ 連絡履歴・操作履歴                │ 注意事項・不足項目       │
└───────────────────────────────────┴──────────────────────────┘
```

発注詳細はページを分割しすぎず、次のタブで構成します。

1. 発注概要
2. 車両情報
3. 引取先・納車先
4. ルート
5. 連絡
6. 操作履歴

右側の操作パネルには次の内容を表示します。

- 現在のステータス
- 受注担当者
- 受注日時
- 「受注する」操作
- 次に遷移可能なステータス
- ステータス変更理由
- 発注者への連絡入力
- 未入力・確認不足項目
- キャンセル状態の警告
- 同時更新時の警告領域

受注確認、ステータス更新、連絡送信のためだけに別ページへ移動せず、
同じ画面内のモーダルまたは操作パネルで完結させます。

```text
POST /orders/{order}/accept
POST /orders/{order}/status
POST /orders/{order}/messages
```

ひな型段階では操作ボタンと表示領域のみ作成し、更新処理は行いません。

## 8. 輸送進捗

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/operations` | `admin/operations/Index.vue` | 輸送中案件の進捗一覧 |
| 中 | GET | `/operations?view=board` | `admin/operations/Index.vue` | ステータス別ボード表示 |
| 中 | GET | `/operations?view=schedule` | `admin/operations/Index.vue` | 引取・納車予定表示 |

次の表示形式を1ページ内で切り替えます。

1. 一覧表示
2. ステータス別ボード
3. 日付別スケジュール

独立した詳細画面は作らず、行またはカードから `/orders/{order}` へ遷移します。

## 9. 通知・連絡

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/notifications` | `admin/notifications/Index.vue` | 画面内通知一覧 |
| 中 | GET | `/messages` | `admin/messages/Index.vue` | 発注単位の連絡一覧 |
| 中 | GET | `/notifications/deliveries` | `admin/notifications/Deliveries.vue` | Push通知の送信結果 |
| 低 | GET | `/notification-settings` | `admin/settings/Notifications.vue` | 通知対象・チャネル設定 |

発注に紐づく連絡本文は発注詳細にも表示し、
`/messages` は未対応連絡を横断確認するための画面とします。

## 10. 得意先管理

発注機能の利用許可、メールによる初期管理者登録、得意先専用ログインURLについては、
[得意先の利用許可・初期管理者登録方針](./customer-onboarding.md)を参照してください。
これは次回実装に向けた方針であり、実装済みの機能ではありません。

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/customers` | `admin/customers/Index.vue` | 得意先一覧・検索 |
| 高 | GET | `/customers/create` | `admin/customers/Create.vue` | 得意先登録 |
| 高 | GET | `/customers/{customer}` | `admin/customers/Show.vue` | 得意先詳細 |
| 高 | GET | `/customers/{customer}/edit` | `admin/customers/Edit.vue` | 得意先編集 |
| 高 | GET | `/customers/{customer}/users` | `admin/customers/Users.vue` | 所属ユーザー一覧 |
| 高 | GET | `/customers/{customer}/accounts/create` | `admin/customers/accounts/Create.vue` | 初期アカウント発行 |
| 中 | GET | `/customers/{customer}/locations` | `admin/customers/Locations.vue` | 得意先の地点一覧 |

得意先詳細は次のタブでまとめます。

1. 基本情報
2. ユーザー
3. 初期アカウント
4. 地点
5. 発注履歴
6. 操作履歴

## 11. ユーザー・ロール管理

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/users` | `admin/users/Index.vue` | 管理側・得意先ユーザー一覧 |
| 高 | GET | `/users/create` | `admin/users/Create.vue` | ユーザー登録 |
| 高 | GET | `/users/{user}` | `admin/users/Show.vue` | ユーザー詳細 |
| 高 | GET | `/users/{user}/edit` | `admin/users/Edit.vue` | ユーザー編集 |
| 中 | GET | `/roles` | `admin/roles/Index.vue` | ロール一覧 |
| 中 | GET | `/roles/{role}` | `admin/roles/Show.vue` | 権限内容の確認 |
| 中 | GET | `/roles/{role}/edit` | `admin/roles/Edit.vue` | 権限設定 |

ユーザー一覧では「陸送会社ユーザー」と「得意先ユーザー」を
タブまたは検索条件で切り替えます。

ロールと権限モデルは未確定のため、ひな型では設定内容を保存しません。

## 12. 地点管理

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 中 | GET | `/locations` | `admin/locations/Index.vue` | 全得意先の地点一覧 |
| 中 | GET | `/locations/create` | `admin/locations/Create.vue` | 地点登録 |
| 中 | GET | `/locations/{location}` | `admin/locations/Show.vue` | 地点詳細 |
| 中 | GET | `/locations/{location}/edit` | `admin/locations/Edit.vue` | 地点編集 |

地点一覧では必ず所属得意先を表示し、同名地点を判別できるようにします。

## 13. マスタ・システム設定

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 中 | GET | `/masters` | `admin/masters/Index.vue` | マスタ管理メニュー |
| 中 | GET | `/masters/vehicle-types` | `admin/masters/VehicleTypes.vue` | 車種マスタ |
| 低 | GET | `/masters/statuses` | `admin/masters/Statuses.vue` | ステータス定義の確認 |
| 中 | GET | `/settings` | `admin/settings/Index.vue` | システム設定メニュー |
| 中 | GET | `/settings/notifications` | `admin/settings/Notifications.vue` | 通知設定 |
| 低 | GET | `/settings/devices` | `admin/settings/Devices.vue` | Push通知端末管理 |

ステータス遷移は業務要件の確定が必要なため、
初期段階では編集画面ではなく参照用とします。

## 14. 監査ログ

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 中 | GET | `/audit-logs` | `admin/audit-logs/Index.vue` | 重要操作の履歴 |
| 中 | GET | `/audit-logs/{auditLog}` | `admin/audit-logs/Show.vue` | 変更前後・実行者・対象 |

監査ログでは次の条件で検索できる想定です。

- 操作日時
- 操作者
- 得意先
- 対象種別
- 対象ID
- 操作種別
- 発注番号

## 15. Vueディレクトリ案

```text
resources/js/pages/admin/
├── Dashboard.vue
├── auth/
│   ├── Login.vue
│   ├── ForgotPassword.vue
│   └── ResetPassword.vue
├── orders/
│   ├── Index.vue
│   └── Show.vue
├── operations/
│   └── Index.vue
├── notifications/
│   ├── Index.vue
│   └── Deliveries.vue
├── messages/
│   └── Index.vue
├── customers/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   ├── Edit.vue
│   ├── Users.vue
│   ├── Locations.vue
│   └── accounts/
│       └── Create.vue
├── users/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   └── Edit.vue
├── roles/
│   ├── Index.vue
│   ├── Show.vue
│   └── Edit.vue
├── locations/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   └── Edit.vue
├── masters/
│   ├── Index.vue
│   ├── VehicleTypes.vue
│   └── Statuses.vue
├── settings/
│   ├── Index.vue
│   ├── Notifications.vue
│   └── Devices.vue
├── audit-logs/
│   ├── Index.vue
│   └── Show.vue
├── errors/
│   ├── Forbidden.vue
│   └── NotFound.vue
└── Help.vue
```

共通レイアウトや再利用可能なUI部品はページディレクトリとは分けて、
`resources/js/layouts/admin/` と `resources/js/components/admin/` へ配置する想定です。

## 16. 実装順序案

### 第1段階：受注業務の主要フロー

1. 共通レイアウト
2. ログイン
3. ダッシュボード
4. 発注管理一覧
5. 発注詳細と右側の受注・進捗操作パネル
6. 輸送進捗一覧

### 第2段階：得意先・利用者管理

1. 得意先一覧
2. 得意先詳細
3. 初期アカウント発行
4. ユーザー一覧
5. ユーザー詳細
6. ロール一覧

### 第3段階：周辺管理機能

1. 通知・連絡
2. 地点管理
3. マスタ管理
4. システム設定
5. 監査ログ
6. ヘルプ・エラー画面

### 実装済みのひな型

第1段階の以下の画面をひな型として実装済みです。

| 画面 | URL | Vue SFC |
| --- | --- | --- |
| ログイン | `/login` | `pages/admin/auth/Login.vue` |
| ダッシュボード | `/` | `pages/admin/Dashboard.vue` |
| 発注管理 | `/orders` | `pages/admin/orders/Index.vue` |
| 発注詳細・受注操作 | `/orders/{order}` | `pages/admin/orders/Show.vue` |
| 輸送進捗 | `/operations` | `pages/admin/operations/Index.vue` |

共通レイアウトは `layouts/admin/AdminLayout.vue`、
サイド・オフキャンバスで共用するナビゲーションは
`components/admin/AdminSideNav.vue` に配置しています。
ログイン画面は認証前の画面のため、共通レイアウトを適用していません。

発注詳細は、発注内容と受注・進捗操作パネルを同じ画面内に表示します。
受注、ステータス更新、連絡送信の各操作は未実装であり、
操作できないことをボタンの無効化と説明文で明示しています。

## 17. ひな型の実装範囲

画面ひな型では、必要な初期値をHTMLシェルからVueページへ渡します。

実装するもの:

- URLと画面遷移
- 受注・システム管理側共通レイアウト
- PC・タブレット・スマートフォン向けのレスポンシブ表示
- サンプルデータを使った一覧・詳細
- 発注詳細と受注操作パネルの同時表示
- ローディング、空データ、エラー、権限不足などの表示状態
- Bootstrapコンポーネントの基本動作

まだ実装しないもの:

- 本番用の認証・認可
- DBへの業務データ登録・更新
- 受注処理、ステータス更新、連絡送信
- 確定した入力検証
- 同時更新・二重受注防止
- Push通知
- メール送信
- 本番用ファイルアップロード

未実装の操作は成功したように見せず、
「ひな型のため未実装」であることを画面上に明示します。

## 18. 今後確定が必要な事項

- 発注ステータスの正式名称と遷移条件
- 各ステータスを更新できるロール
- 受注可能な条件と二重受注防止
- 受注担当者の割り当て方法
- 輸送担当者・ドライバー・協力会社を管理対象に含めるか
- 遅延・期限超過の判定条件
- 発注一覧の検索対象、既定の並び順、1回あたりの取得件数
- 輸送進捗の一覧、ボード、スケジュール表示の優先順位
- 得意先の登録項目、無効化・削除条件
- 初期アカウントの発行・通知方法
- 管理側ユーザーと得意先ユーザーのロール・権限モデル
- 地点マスタの管理範囲
- 車種などのマスタ管理項目
- 監査ログの記録対象と保持期間
- 通知イベント、通知方法、保持期間
- Push通知、メール等の外部サービス
