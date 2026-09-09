COACHTECH お問い合わせフォーム
1. 概要
本プロジェクトは、バックエンド開発の基礎力を証明するため、Laravelを用いたTraditional Web（Bladeテンプレート/セッション認証）構成によるお問い合わせ管理システムです。
一般ユーザー向けのお問い合わせ入力・確認・送信完了フローに加え、管理者向けのログイン・お問い合わせ検索・一覧表示・詳細確認・タグマスタ管理、CSVエクスポート機能を備えています。
また、応用要件として、外部システムと連携可能なお問い合わせデータCRUD操作用の公開API（v1）を実装しています。

2. 使用技術（技術スタック）
OS / インフラ: Docker / Laravel Sail (Nginx, MySQL 8.0)
バックエンド: PHP 8.2 / Laravel 10.xフロントエンド: Vite / Tailwind CSS ^3.4.0 / Alpine.js
認証: Laravel Fortify
コード品質・テスト: Laravel Pint (PSR-12準拠) / PHPUnit (Xdebugカバレッジ71.6%達成)

3. 環境構築手順以下の手順に従うことで、誰でも簡単に開発環境を構築できます。① リポジトリのクローンと移動bashgit clone <あなたのGitHubリポジトリURL>
cd contact-form-app
コードは注意してご使用ください。
② .envファイルの作成.env.example をコピーして .env を作成し、以下の接続情報と一致していることを確認します。iniDB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
コードは注意してご使用ください。
③ 依存パッケージのインストールとSailの起動bash# Composerパッケージのインストール
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html laravelsail/php82-composer:latest composer install

# コンテナをバックグラウンドで起動
./vendor/bin/sail up -d

# フロントエンドパッケージのインストールとビルド
sail npm install
sail npm run build
コードは注意してご使用ください。④ アプリケーションキー生成と初期データ投入bash# アプリケーションキーの生成
sail artisan key:generate

# データベースのマイグレーション & シーダー実行 (テストデータ20件自動生成)
sail artisan migrate:fresh --seed
コードは注意してご使用ください。
4. 開発環境URLフロントエンド（お問い合わせフォーム）: http://localhost/contacts管理画面（要ログイン）: http://localhost/admin初期管理者アカウント:メールアドレス: test@example.comパスワード: passwordphpMyAdmin（データベース管理ツール）: http://localhost:8080

5. APIエンドポイント一覧公開API（ルートプレフィックス: /api/v1、認証不要）の検索・詳細取得・作成・更新・削除などのエンドポイント一覧が含まれます。

6. 作者: 吾妻潤一

ER図
+-------------------+       +-------------------+
| categories        |       | tags              |
+-------------------+       +-------------------+
| PK id             |       | PK id             |
|    content        |       |    name (UNIQUE)  |
|    created_at     |       |    created_at     |
|    updated_at     |       |    updated_at     |
+-------------------+       +-------------------+
          |                           |
          | 1対多                     | 1対多
          ↓                           ↓
+-------------------+       +-------------------+
| contacts          |       | contact_tag       |
+-------------------+       +-------------------+
| PK id             | <----+| PK id             |
| FK category_id    | 1対多 || FK contact_id     |
|    first_name     |       || FK tag_id        |
|    last_name      |       ||    created_at    |
|    gender         |       ||    updated_at    |
|    email          |       |+-------------------+
|    tel            |       | (UNIQUE: contact_id, tag_id)
|    address        |
|    building       |
|    detail         |
|    created_at     |
|    updated_at     |
+-------------------+

+-------------------+
| users             |
+-------------------+
| PK id             |
|    name           |
|    email (UNIQUE) |
|    email_verified |
|    password       |
|    remember_token |
|    created_at     |
|    updated_at     |
+-------------------+