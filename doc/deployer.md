# Docker での Deployer 実行

ローカルに PHP をインストールせず、専用コンテナの PHP 8.4 で Deployer を実行します。
Laravel の開発コンテナや DB の起動は不要です。
Deployer の依存関係は `deploy/` に分離し、アプリの依存関係には追加しません。

**現在の対応範囲は実行環境、SSH 接続設定例、Release の解決・確認です。**
デプロイ・イメージ取得・マイグレーション・Nginx 切り替え・ロールバックのタスクは未実装です。

## 初回セットアップ

プロジェクトルートで実行します。

```bash
docker compose -f docker/compose.deployer.yaml build
docker compose -f docker/compose.deployer.yaml run --rm \
  --entrypoint composer deployer install --no-interaction --prefer-dist
cp -n deploy/hosts.yml.example deploy/hosts.yml
```

Linux で UID/GID が 1000 以外の場合は、実行前に `LOCAL_UID` / `LOCAL_GID` を
ホストの `id -u` / `id -g` の値で export してください。変更後はイメージを再ビルドします。
依存関係は `deploy/composer.lock` に固定し、更新時以外は `composer install` を使います。

`deploy/hosts.yml` のホスト名・SSH ユーザー・ポート・鍵ファイル名を設定します。
`deploy_path` は今後のデプロイ用の配置先案で、接続確認では作成・変更しません。
このファイルは Git 管理外です。秘密鍵や GHCR トークンをリポジトリへ保存しないでください。

## SSH 設定

ホストの `~/.ssh` をコンテナ内の `/home/deployer/.ssh` に読み取り専用でマウントします。
別のディレクトリを使う場合は、`DEPLOY_SSH_DIR` にその絶対パスを export してください。
接続先の鍵は、ホスト側でパーミッションを適切に制限してください。

ホスト鍵検証は必須です。信頼できる経路で接続先のホスト鍵のフィンガープリントを確認し、
接続先・ポートに対応するエントリーをホスト側の `known_hosts` に事前登録してください。
読み取り専用マウントのため、コンテナから `known_hosts` を更新することはできません。

Deployer の SSH 接続共有ソケットは、書き込み可能なコンテナ内の
`/home/deployer/.ssh-control-%C` に作成します。既定の `~/.ssh` 配下では
`unix_listener: cannot bind ... Read-only file system` になるため、保存先を分けています。
`%C` は OpenSSH が接続先などから生成するハッシュです。

接続例は鍵ファイルによる認証です。SSH エージェントのソケットはマウントしません。
パスフレーズ付きの鍵は対話的に実行してください。エージェント利用や無人実行は別途設計します。

## ローカルでの起動確認

SSH 接続せず、Deployer のバージョンとタスク一覧を確認します。

```bash
docker compose -f docker/compose.deployer.yaml run --rm deployer --version
docker compose -f docker/compose.deployer.yaml run --rm deployer list
```

`deploy/hosts.yml` がなくても、この2つのコマンドは実行できます。
ただし、マウント元の SSH ディレクトリは存在する必要があります。

## SSH 接続確認

接続先には Docker Engine と Compose が必要です。未導入の場合は、以下のサーバー準備を先に行います。

### 本番サーバーへの Docker 導入（Ubuntu 24.04 LTS）

Ubuntu 24.04.3 LTS（Noble）を含む Ubuntu 24.04 向けの手順です。
**この節のコマンドはローカルや Deployer コンテナ内ではなく、SSH 接続先の本番サーバーで実行します。**
sudo 権限が必要です。ホスト側の PHP / Node.js は、このイメージ取得方式では不要です。

#### 1. 既存環境の確認

```bash
cat /etc/os-release
command -v docker
dpkg -l docker.io docker-compose docker-compose-v2 docker-doc docker-buildx \
  podman-docker containerd runc
```

未インストールのパッケージは「見つからない」と表示されることがあります。
既存のコンテナやサービスがある場合は、停止・パッケージ置換の影響を確認してから進めてください。
Docker 公式パッケージと競合する既存パッケージは、必要なものだけを確認の上で除去します。
この手順では一括削除や既存データの削除は行いません。

#### 2. Docker 公式 APT リポジトリの登録

```bash
sudo apt update
sudo apt install ca-certificates curl
sudo install -m 0755 -d /etc/apt/keyrings
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg \
  -o /etc/apt/keyrings/docker.asc
sudo chmod a+r /etc/apt/keyrings/docker.asc

sudo tee /etc/apt/sources.list.d/docker.sources > /dev/null <<EOF
Types: deb
URIs: https://download.docker.com/linux/ubuntu
Suites: noble
Components: stable
Architectures: $(dpkg --print-architecture)
Signed-By: /etc/apt/keyrings/docker.asc
EOF

sudo apt update
```

既存の Docker 用 APT 設定がある場合は、重複登録や上書きにならないよう事前に確認してください。

#### 3. Docker Engine・Compose プラグインのインストール

```bash
sudo apt install docker-ce docker-ce-cli containerd.io \
  docker-buildx-plugin docker-compose-plugin
sudo systemctl enable --now docker
sudo systemctl is-active docker
sudo docker version
docker compose version
sudo docker run --rm hello-world
```

Compose は `docker-compose` ではなく `docker compose` を使います。
この手順は実行時点の安定版を導入します。運用開始後の更新は、動作確認と計画的な適用を行ってください。
`hello-world` の初回実行には Docker Hub への通信が必要です。

#### 4. デプロイユーザーの権限設定

以下の `DEPLOY_USER` を、`deploy/hosts.yml` の `remote_user` と同じ実在ユーザー名へ変更します。
`$USER` は管理者の可能性があるため、対象ユーザーを明示してください。

**docker グループへの所属は実質的に root 相当の権限を与えます。**
信頼できるデプロイ専用ユーザーに限定し、権限付与が許容されるか確認してから実行してください。
Docker ソケットのパーミッションを `666` に変更する方法は使いません。

```bash
DEPLOY_USER=deploy
id "$DEPLOY_USER"
sudo usermod -aG docker "$DEPLOY_USER"
```

対象ユーザーの既存 SSH セッションを閉じ、新規ログインしてから確認します。
管理者ではなく、実際のデプロイユーザーで実行してください。

```bash
id
docker version
docker compose version
docker ps
```

Docker API の TCP ポート（2375 / 2376）を開放する必要はありません。
コンテナの公開ポートは UFW の制御を迂回する場合があるため、UFW だけに依存せず、
LTBS の Web ポートは引き続き `127.0.0.1` に限定し、DB や PHP-FPM は外部公開しないでください。

公式資料：
[Ubuntu へのインストール](https://docs.docker.com/engine/install/ubuntu/) /
[非 root ユーザーでの利用](https://docs.docker.com/engine/install/linux-postinstall/)

### Deployer からの再確認

本番サーバーの接続情報とホスト鍵を設定した後、明示的に実行します。
以下はローカル側のプロジェクトルートで実行します。

```bash
docker compose -f docker/compose.deployer.yaml run --rm deployer ssh:check prod
```

リモートで `whoami` と `docker compose version` だけを実行します。
Docker デーモンの操作権限や GHCR 認証、アプリの動作までは検証しません。
デプロイ用ユーザーの Docker 操作権限は、本番デプロイ実装時に確認します。
SSH の転送エージェントは無効で、ローカルの Docker ソケットもコンテナに渡しません。

## npm scripts からの実行

ホスト側に Node.js / npm がある場合、プロジェクトルートから次のようにも実行できます。
PHP は Docker 内のものを使います。初回セットアップは先に完了させてください。

```bash
npm --prefix src run dep -- --version
npm --prefix src run dep -- list
npm --prefix src run dep -- ssh:check prod
```

`--` 以降の引数を Deployer に渡します。Docker を操作するため、Vite コンテナ内ではなく
ホスト側のターミナルで実行してください。依存パッケージのインストールは行いません。
`deploy` タスクは未実装のため、このコマンドを追加しても本番反映はまだできません。

## Release の解決・確認

Actions は両イメージの公開成功後に GitHub Release を作成します。
タグに `-` を含む場合（例: `v0.1.0-test.2`）は Prerelease として作成します。
Release が既に存在する場合は変更しません。公開後のタグ・イメージの上書きは避けてください。

```bash
# 最新の正式 Release（GitHub の latest API。最大のタグ文字列ではない）
npm --prefix src run dep -- release:check prod

# 指定 Release。Prerelease の明示指定も可能
npm --prefix src run dep -- release:check prod --tag=v0.1.0-test.2
```

GitHub API は SSH 接続先サーバーから呼び出します。タグがあっても Release がなければ失敗します。
正式 Release がまだない場合、タグ省略での確認は失敗します。
このタスクは Release と想定されるイメージ名の確認のみで、
GHCR 上のイメージの存在確認・digest 取得・pull・デプロイ・ロールバックは行いません。

### GitHub トークン

サーバーの SSH 接続ユーザーのホーム直下に **`~/.ltbs-github-token`** を置きます。
ファイルにはトークンだけを1行で保存し、所有者を接続ユーザー、権限を `600`（または `400`）にします。
トークンをコマンド引数や Git、ログ、チャットへ記載しないでください。
配置はサーバー上のエディターなど安全な方法で行います。

```bash
# 本番サーバーで、実際の SSH 接続ユーザーとして実行
umask 077
touch "$HOME/.ltbs-github-token"
chmod 600 "$HOME/.ltbs-github-token"
# エディターでトークンを保存（既存トークンを不用意に上書きしない）
```

非公開リポジトリの Release 取得には、そのリポジトリを参照できる権限が必要です。
今後の GHCR pull と同じ PAT（classic）を共用する場合は `read:packages` に加え、
非公開リポジトリの Release API 用の `repo` 権限が必要です。`repo` は広い権限なので、
アクセス対象を限定した専用アカウントを利用するか、API 用 Fine-grained PAT
（対象リポジトリの Contents: read）と GHCR 用 PAT（classic）の分離を検討してください。
今回のタスクはこのファイルで API 認証するだけで、`docker login` は行いません。

ローカルの解決ロジックのテスト（SSH 接続なし）：

```bash
docker compose -f docker/compose.deployer.yaml run --rm \
  --entrypoint php deployer tests/release-check.php
```

## 今後の対応

デプロイ方式、イメージタグ・digest の記録、ヘルスチェック、ロールバック、
GHCR の認証方法は[本番配置の検討事項](./production.md#デプロイ自動化次回対応)を参照してください。
Blue-Green の場合は、本番ホスト Nginx の切り替え権限や共有ボリュームの設計も必要です。
