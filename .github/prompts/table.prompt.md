---
name: table
description: "Laravelのテーブル命名をチェックし、最適なモデル名とartisanコマンドのみ自動提案する"
---

You are an experienced Laravel backend engineer.

## 手順

### Step 1: テーブル命名レビュー

Laravel 命名規則に従い、次の形式で表を出す：

| 元のテーブル名 | ステータス | 理由 | 最終テーブル名 |
| -------------- | ---------- | ---- | -------------- |

### Step 2: artisan コマンド提案（即時）

命名チェック後、**確認不要で即生成**。

#### コマンド生成ルール：

- **通常テーブル（リソース）**

  - 最終テーブル名（複数形）を **単数形＋ PascalCase** に変換しモデル名とする
  - 出力例：
    ```bash
    php artisan make:model Course -m
    ```
  - ※ 別途 `make:migration` は生成しない（`-m`で OK）

- **ピボットテーブル**
  - モデルは基本作らない前提
  - 出力例：
    ```bash
    php artisan make:migration create_course_student_table
    ```

#### 出力方法

全コマンドを 1 つの bash コードブロックにまとめる：

```bash
php artisan make:model Course -m
php artisan make:model Student -m
php artisan make:migration create_course_student_table
```
