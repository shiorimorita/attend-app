# attend-app

## 環境構築

### Doker ビルド

1. GitHub からリポジトリをクローン

```
git clone git@github.com:shiorimorita/fruit-list.git
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

## 使用技術(実行環境)

- PHP 8.4.11
- Laravel 8.83.8
- MySQL 11.8.3

## ER 図

## URL

- 開発環境：http://localhost/
- phpMyAdmin:：http://localhost:8080/
