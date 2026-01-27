# attend-app

## 環境構築

### Doker ビルド

1. GitHub からリポジトリをクローン

```
git clone git@github.com:shiorimorita/attend-app.git
```

2. クローンしたリポジトリのディレクトリに移動する
3. DockerDesktop アプリを立ち上げる

```
docker-compose up -d --build
```

※ Mac の M1・M2 チップの PC の場合、no matching manifest for linux/arm64/v8 in the manifest list entries のメッセージが表示されビルドができないことがあります。 エラーが発生する場合は、docker-compose.yml ファイルの「mysql」内に「platform」の項目を追加で記載してください

```yaml
mysql:
  platform: linux/x86_64(この行を追加)
  image: mysql:8.0.26
  environment:
```

### Laravel 環境構築

1. docker-compose exec php bash
2. composer install
3. 「.env.example」ファイルを 「.env」ファイルに命名を変更。
4. アプリケーションキーの作成

```
php artisan key:generate
```

5. マイグレーションの実行

```
php artisan migrate
```

6. シーディングの実行

```
php artisan db:seed
```

## ダミーデータ

以下のダミーデータが用意されています：

### ユーザーデータ

- **管理者アカウント**
  - メールアドレス: admin@example.com
  - パスワード: password
  - ロール: admin

- **スタッフアカウント**
  - メールアドレス: staff@example.com
  - パスワード: password
  - ロール: staff

### 勤怠データ

- スタッフアカウント（staff@example.com）の過去 30 日分の勤怠記録
- 出勤時刻: 09:00
- 退勤時刻: 18:00 ～ 19:00 の間でランダム
- 休憩時間:
  - 必須休憩 12:00 ～ 13:00（1 時間）
  - ランダムで追加休憩（10 ～ 30 分）

## 使用技術(実行環境)

- PHP 8.4.11
- Laravel 8.83.8
- MySQL 11.8.3

## ER 図

<img width="602" height="1082" alt="Image" src="https://github.com/user-attachments/assets/d1cc3889-f0d2-491e-93e1-e4f0375d9a51" />
## URL

- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/
- MailHog : http://localhost:8025/
