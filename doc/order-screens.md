# 発注側画面・URL設計案

## 1. 文書の位置付け

本書は、発注側アプリケーションで作成する画面ひな型とURLの初期案を整理したものです。
[要件定義書（たたき台）](./要件定義書_たたき台.md)をもとにしていますが、
認証方式、権限、業務ステータス、入力項目、外部サービス連携は未確定です。

初期の画面ひな型では、画面遷移、レスポンシブレイアウト、入力項目、各状態の表示を確認し、
DB登録、認証・認可、Google Maps、OCR、Push通知などの実処理は実装しません。

## 2. URL方針

発注側は専用ドメインで提供するため、パスに `/order` は付けません。

```text
開発環境: http://order.localhost:${APP_PORT}
既定値:   http://order.localhost:8000
本番例:   https://order.example.jp
```

開発環境のポートは `docker/.env` の `APP_PORT` に従います。
発注ID、ユーザーID、地点IDの形式は未確定のため、本書ではそれぞれ
`{order}`、`{user}`、`{location}` と表記します。

## 3. 共通レイアウト

認証後の画面は、次の共通レイアウトを使用する想定です。

- ヘッダー
  - ロゴ・アプリケーション名
  - フリーワード検索
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
新規発注             /orders/create
発注履歴             /orders
地点履歴             /locations
通知                 /notifications
ユーザー管理         /users
アカウント設定       /account/profile
ヘルプ               /help
```

スマートフォン用下部ナビゲーションは、使用頻度を考慮して次の4項目に絞ります。

```text
ホーム / 発注する / 発注履歴 / 通知
```

## 4. 共通・認証画面

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/login` | `order/auth/Login.vue` | ログインID、パスワード、表示切替、ログイン状態保持 |
| 中 | GET | `/forgot-password` | `order/auth/ForgotPassword.vue` | パスワード再設定メールの送信 |
| 中 | GET | `/reset-password/{token}` | `order/auth/ResetPassword.vue` | 新しいパスワードの設定 |
| 高 | GET | `/` | `order/Dashboard.vue` | 発注状況、新規発注導線、最近の発注、未読通知 |
| 低 | GET | `/help` | `order/Help.vue` | 操作案内、問い合わせ先、FAQ |
| 低 | GET | `/forbidden` | `order/errors/Forbidden.vue` | 権限不足表示 |
| 低 | GET | `/not-found` | `order/errors/NotFound.vue` | ページが見つからない場合の表示 |

認証が実装されるまでは、ログイン関連画面は表示確認用モックとし、
業務画面には直接アクセスできる状態とします。

## 5. ダッシュボード

### `GET /`

ダッシュボードには次の内容を表示します。

- 新規発注ボタン
- ステータス別件数
  - 仮発注
  - 受注済み
  - 発注完了
  - キャンセル
- 最近の発注
- 対応が必要な発注
- 未読通知
- Push通知が無効な場合の案内

ステータス名と集計対象は業務確認後に確定します。

## 6. 発注履歴・詳細

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 最優先 | GET | `/orders` | `order/orders/Index.vue` | 発注履歴、検索、絞り込み、並び順、無限スクロール |
| 最優先 | GET | `/orders/{order}` | `order/orders/Show.vue` | 発注、車両、地点、日時、ステータスの詳細 |
| 高 | GET | `/orders/{order}?tab=route` | `order/orders/Show.vue` | ルート、距離、所要時間 |
| 高 | GET | `/orders/{order}?tab=messages` | `order/orders/Show.vue` | 陸送会社からの連絡 |
| 中 | GET | `/orders/{order}?tab=history` | `order/orders/Show.vue` | ステータス変更・操作履歴 |
| 高 | GET | `/orders/{order}/reorder` | `order/orders/Reorder.vue` | 過去の発注内容を引き継いだ再発注 |

発注一覧では、次の検索・表示項目を用意します。

- フリーワード
- 発注ID・発注番号
- 車両番号・車台番号
- 発注日・出発日
- ステータス
- 発注者
- 出発地・到着地
- 並び順
- 無限スクロール

検索条件と並び順はURLのクエリーパラメーターへ反映します。
一覧データはカーソルページネーションで分割取得し、Inertia.jsの
無限スクロール機能で追加表示します。共通の実装方針は
[構成概要](architecture.md#一覧テーブルと無限スクロール)に従います。

発注詳細はページを分割しすぎず、次のタブで構成します。

1. 発注概要
2. 車両情報
3. 出発地・到着地
4. ルート
5. 連絡
6. 操作履歴

キャンセルは独立した画面ではなく、発注詳細上の確認モーダルから実行する想定です。

```text
POST /orders/{order}/cancel
```

ひな型段階ではボタン、理由入力、確認モーダルだけを作成し、更新処理は行いません。

## 7. 新規発注画面

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 最優先 | GET | `/orders/create` | `order/orders/Create.vue` | 入力フォームと発注内容プレビューの同時表示 |
| 最優先 | POST | `/orders` | なし | 発注登録。実装後は詳細へリダイレクト |
| 高 | GET | `/orders/{order}?created=1` | `order/orders/Show.vue` | 登録完了通知と登録内容 |

昔ながらの入力画面と確認画面を明確に分ける構成にはしません。
入力中の内容を同じ画面内のプレビューへリアルタイムに反映し、
利用者が入力と完成イメージを行き来せず確認できる構成とします。

想定する画面遷移は次のとおりです。

```text
/orders/create（入力フォーム + リアルタイムプレビュー）
    ↓ 発注登録
/orders/{order}?created=1
```

### 7.1 画面レイアウト

デスクトップでは、入力領域とプレビュー領域を横並びにします。

```text
┌──────────────────────────────────────────────────────────────┐
│ ヘッダー                                                     │
├───────────────────────────────────┬──────────────────────────┤
│ 入力フォーム                      │ 発注内容プレビュー       │
│                                   │                          │
│ 車両情報                          │ ステータス               │
│ 出発希望日時                      │ 車両                     │
│ 出発地                            │ 出発地 → 到着地          │
│ 到着希望日時                      │ 日時                     │
│ 到着地                            │ 備考                     │
│ 備考・画像                        │                          │
│                                   │ 入力不足項目の案内       │
│                        発注する → │                          │
└───────────────────────────────────┴──────────────────────────┘
```

- 入力領域を画面幅の約60～65%とします。
- プレビュー領域を約35～40%とし、スクロール時も確認できるよう追従表示します。
- 入力値はプレビューへ即時反映します。
- 未入力項目は空欄にせず、「未入力」「選択してください」などの状態を表示します。
- 入力エラーはフォーム項目の近くに表示し、プレビュー側でも不足項目を把握できるようにします。
- 発注詳細画面に近い見た目でプレビューし、登録後の表示との差を小さくします。

タブレットでは画面幅に応じて比率を調整し、狭い場合はプレビューを開閉可能な
サイドパネルにします。スマートフォンでは横並びを強制せず、入力内容の要約を画面下部へ
常時表示し、そこからプレビューを全画面またはOffcanvasで確認できる構成とします。
プレビューを開閉しても入力内容とスクロール位置を失わないようにします。

発注実行前に最終確認が必要な場合も、独立した確認画面へ遷移せず、
発注番号の発行を伴う操作であることを示す簡潔な確認モーダルを使用します。

### 7.2 入力領域

1. 車両情報
2. 出発希望日時
3. 出発地
4. 到着希望日時
5. 到着地
6. 備考・特記事項
7. 添付画像
8. 発注ボタン

得意先はログイン情報からサーバー側で決定し、発注者が選択・変更する項目にはしません。

### 7.3 プレビュー領域

プレビューには次の内容を表示します。

- 発注者と得意先
- 車種・車台番号・車両番号
- 車両番号画像またはプレビュー
- 出発希望日時
- 出発地の名称・住所・担当者・TEL
- 到着希望日時
- 到着地の名称・住所・担当者・TEL
- 備考・特記事項
- 入力済み・未入力の状態
- 入力エラーまたは不足項目の要約

プレビューは確認専用とし、値の修正は対応する入力項目へ戻って行います。

## 8. 車両番号画像・OCR

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 中 | GET | `/orders/create/vehicle-image` | `order/orders/VehicleImage.vue` | 画像選択、プレビュー、読取中表示 |
| 中 | GET | `/orders/create/vehicle-image/confirm` | `order/orders/VehicleImageConfirm.vue` | 読取候補の確認、修正、手入力切替 |

実装時には独立ページではなく、`Create.vue` 内のステップまたはモーダルへ統合する可能性があります。
ひな型では次の状態を表示できるようにします。

- 画像未選択
- プレビュー表示
- 読取処理中
- 読取成功
- 複数候補
- 読取失敗
- 手入力への切り替え

OCRサービスへの画像送信は、利用サービスと画像の保存・削除方針を確定してから実装します。

## 9. 地点選択・地点履歴

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/locations` | `order/locations/Index.vue` | 過去に利用した引取先・納車先の検索と選択 |
| 中 | GET | `/locations/create` | `order/locations/Create.vue` | 地点の手入力登録 |
| 中 | GET | `/locations/{location}/edit` | `order/locations/Edit.vue` | 保存済み地点の編集 |
| 高 | GET | `/map/select?target=pickup` | `order/map/Select.vue` | 出発地の地図選択 |
| 高 | GET | `/map/select?target=delivery` | `order/map/Select.vue` | 到着地の地図選択 |

地図選択画面には次の領域を用意します。

- 地名・施設名・住所検索
- 地図表示領域
- 中心ピン
- 緯度・経度
- 住所候補
- フォームへ反映するボタン
- 手入力へ切り替えるボタン

Google Maps APIが未設定でもレイアウトを確認できるよう、ひな型では地図を
プレースホルダー表示にします。

## 10. 通知

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 高 | GET | `/notifications` | `order/notifications/Index.vue` | 未読・既読通知の一覧 |
| 中 | GET | `/notifications/{notification}` | 原則として専用画面なし | 既読化して対象発注へ遷移 |
| 中 | GET | `/settings/notifications` | `order/settings/Notifications.vue` | 画面内・メール・Push通知設定 |
| 低 | GET | `/settings/devices` | `order/settings/Devices.vue` | Push通知を許可した端末一覧 |

通知一覧では次のサンプル状態を用意します。

- 新規発注受付
- 受注完了
- 発注完了
- キャンセル
- 陸送会社からの連絡
- 未読・既読
- Push通知を利用できない場合の案内

## 11. 自社ユーザー管理

自社ユーザー管理は、得意先管理者だけが利用できる想定です。

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 中 | GET | `/users` | `order/users/Index.vue` | 自社ユーザーの検索・一覧 |
| 中 | GET | `/users/create` | `order/users/Create.vue` | 自社ユーザー追加 |
| 中 | GET | `/users/{user}` | `order/users/Show.vue` | ユーザー情報、状態、最終ログイン |
| 中 | GET | `/users/{user}/edit` | `order/users/Edit.vue` | 名前、メール、TEL、ロール、状態の編集 |

ユーザーの無効化は編集画面または一覧の確認モーダルから行います。
削除は監査上の扱いが未確定なため、現段階では用意しません。

## 12. アカウント設定

| 優先度 | Method | URL | Vueページ | 主な内容 |
| --- | --- | --- | --- | --- |
| 中 | GET | `/account/profile` | `order/account/Profile.vue` | 氏名、メールアドレス、TEL |
| 中 | GET | `/account/password` | `order/account/Password.vue` | パスワード変更 |
| 中 | GET | `/account/security` | `order/account/Security.vue` | ログイン情報、セッション確認用の枠 |
| 中 | GET | `/settings/notifications` | `order/settings/Notifications.vue` | 通知方法の設定 |

## 13. Vueディレクトリ案

```text
resources/js/pages/order/
├── Dashboard.vue
├── auth/
│   ├── Login.vue
│   ├── ForgotPassword.vue
│   └── ResetPassword.vue
├── orders/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   ├── Reorder.vue
│   ├── VehicleImage.vue
│   └── VehicleImageConfirm.vue
├── locations/
│   ├── Index.vue
│   ├── Create.vue
│   └── Edit.vue
├── map/
│   └── Select.vue
├── notifications/
│   └── Index.vue
├── users/
│   ├── Index.vue
│   ├── Create.vue
│   ├── Show.vue
│   └── Edit.vue
├── account/
│   ├── Profile.vue
│   ├── Password.vue
│   └── Security.vue
├── settings/
│   ├── Notifications.vue
│   └── Devices.vue
├── errors/
│   ├── Forbidden.vue
│   └── NotFound.vue
└── Help.vue
```

共通レイアウトや再利用可能なUI部品は、ページディレクトリとは分けて
`resources/js/layouts/order/` と `resources/js/components/order/` へ配置する想定です。

## 14. 実装順序案

### 第1段階：主要業務フロー

1. 共通レイアウト
2. ダッシュボード
3. 発注履歴
4. 発注詳細
5. 新規発注（入力フォームとリアルタイムプレビュー）
6. 再発注
7. 地図選択プレースホルダー

### 第2段階：周辺機能

1. 地点履歴
2. 通知一覧
3. アカウント設定
4. ユーザー管理
5. ヘルプ・エラー画面

### 第3段階：外部連携を前提とする画面状態

1. OCR画像読取
2. Google Maps連携状態
3. Push通知設定
4. 端末管理

### 実装済みのひな型

第1段階のうち、以下をひな型として実装済みです（7. 地図選択プレースホルダーは未着手）。

| 画面 | URL | Vue SFC |
| --- | --- | --- |
| ログイン | `/login` | `pages/order/auth/Login.vue` |
| ダッシュボード | `/` | `pages/order/Dashboard.vue` |
| 発注履歴 | `/orders` | `pages/order/orders/Index.vue` |
| 新規発注 | `/orders/create` | `pages/order/orders/Create.vue` |
| 発注詳細 | `/orders/{order}` | `pages/order/orders/Show.vue` |
| 再発注 | `/orders/{order}/reorder` | `pages/order/orders/Reorder.vue` |

共通レイアウトは `layouts/order/OrderLayout.vue`、
サイド・オフキャンバスで共用するナビゲーションは
`components/order/OrderSideNav.vue` に配置しています。
ログイン画面は認証前の画面のため、共通レイアウトを適用していません。

ルーティングは `routes/web.php` が `APP_PORTAL` を検証したうえで
`routes/order.php` または `routes/admin.php` を読み込みます。
これにより、一方の環境に他方のルートが登録されません。

アプリケーション名とポータル種別は `HandleInertiaRequests` の共有プロパティとして渡し、
型は `resources/js/types/inertia.d.ts` で `InertiaConfig` に宣言マージしています。

各画面の中身はページタイトルと領域の枠のみで、業務データは扱いません。
未実装の導線はリンクを無効化し、「未実装」と表示しています。

## 15. ひな型の実装範囲

画面ひな型では、Laravelから静的なサンプルデータをInertia propsとして渡します。

実装するもの:

- URLと画面遷移
- 発注側共通レイアウト
- PC・タブレット・スマートフォン向けのレスポンシブ表示
- サンプルデータを使った一覧・詳細
- 入力フォームの外観
- 入力内容を即時反映する発注プレビュー
- ローディング、空データ、エラー、権限不足などの表示状態
- Bootstrapコンポーネントの基本動作

まだ実装しないもの:

- 本番用の認証・認可
- DBへの業務データ登録・更新
- 確定した入力検証
- Google Maps API通信
- OCR・画像認識サービス
- PWA・Service Worker
- Push通知
- メール送信
- 本番用ファイルアップロード

未実装の操作は成功したように見せず、「ひな型のため未実装」であることを画面上に明示します。

## 16. 今後確定が必要な事項

- 発注ステータスと遷移条件
- キャンセル可能な条件
- 発注番号と各IDの形式
- 入力項目、必須条件、桁数
- 一覧の検索対象、既定の並び順、1回あたりの取得件数
- デスクトップ・タブレット・スマートフォンそれぞれのプレビュー表示方法
- 発注実行時に確認モーダルを表示するか
- OCR画面を独立ページにするか、発注フォームへ統合するか
- 地図選択を独立ページにするか、モーダルにするか
- 得意先管理者と一般発注者の権限差
- 通知イベント、通知方法、保持期間
- PWAとPush通知の採用範囲
- Google Maps、OCR、メール等の外部サービス
