# 勤怠アプリ

---

## 環境構築🔗

### Dockerビルド手順

1. `git clone git@github.com:karun119/worklog-test.git`
2. `cd worklog-test`
3. `docker-compose up -d --build`

> ※MySQLは、OSによって起動しない場合があります。  
> 必要に応じて、ご自身のPC環境に合わせて `docker-compose.yml` ファイルを編集してください。
>
> ⚠️ 補足（Macユーザー向け）  
> 本リポジトリでは Mac（M1・M2）での MySQL 起動に対応するため、  
> `docker-compose.yml` に以下を記載済みです。
> 
```yaml
mysql:
    platform: linux/x86_64   # ← この行を追加しています
    image: mysql:8.0.26
    environment:
```
>
> そのため、`docker-compose.yml` を直接編集せずに  
> `docker-compose up -d --build` で起動可能です。

---

### Laravel環境構築手順🔗

1. `docker-compose exec php bash`
2. `composer install`
3. `.env.example` から `.env` を作成  
   `cp .env.example .env`
4. `.env` に以下の環境変数を追加してください。

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel_db
DB_USERNAME=laravel_user
DB_PASSWORD=laravel_pass
```

5. `php artisan key:generate`  
6. `php artisan migrate`  
7. `php artisan db:seed`

---

### メール認証環境（MailHog）📧

MailHog を使用することで、開発中のメール送信内容をローカルのWeb画面で確認できます。  
（実際の送信はされません）

#### メール送信設定手順🔗

1. `.env` に以下を追記してください。

```env
MAIL_MAILER=smtp  
MAIL_HOST=mailhog  
MAIL_PORT=1025  
MAIL_USERNAME=null  
MAIL_PASSWORD=null  
MAIL_ENCRYPTION=null  
MAIL_FROM_ADDRESS=test@example.com  
MAIL_FROM_NAME="${APP_NAME}"
```

2. 設定変更後は以下のコマンドでキャッシュをクリアしてください。  
   `php artisan config:clear`

---

## ユーザー情報🔗

---

## PHPUnitを利用したテスト環境の手順🔗

### テスト用データベースの作成

```bash
docker-compose exec mysql bash
mysql -u root -p
# パスワードは「root」と入力
create database test_database;
exit
```

### PHPコンテナに入ります

```bash
docker-compose exec php bash
```

### テスト環境用のマイグレーションを実行

```bash
php artisan migrate:fresh --env=testing
```

### テストを実行

```bash
./vendor/bin/phpunit
```

または Laravel コマンドで実行する場合：

```bash
php artisan test
```

---

### 補足

- テスト設定は `phpunit.xml` に記述されています。    

---

## 使用技術🔗

- Laravel: 8.83.29  
- PHP: 8.1.33  
- Composer: 2.8.12  
- MySQL: 8.0.26  
- Nginx: 1.21.1  
- [phpMyAdmin（http://localhost:8080）](http://localhost:8080)  
- [MailHog（http://localhost:8025）](http://localhost:8025)  
- Docker / Docker Compose

---

## ER図🔗


![ER図]()



---

# テーブル仕様

## usersテーブル

| カラム名 | 型 | PRIMARY KEY | UNIQUE KEY | NOT NULL | FOREIGN KEY |
|-----------|----|-------------|------------|----------|-------------|
| id | unsigned bigint | ○ |  | ○ |  |
| name | varchar(255) |  |  | ○ |  |
| email | varchar(255) |  | ○ | ○ |  |
| email_verified_at | timestamp |  |  |  |  |
| password | varchar(255) |  |  | ○ |  |
| admin_status | enum('admin','general') |  |  | ○ |  |
| attendance_status | enum('before_work','working','break','after_work') |  |  | ○ |  |
| remember_token | varchar(100) |  |  |  |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

---

## attendancesテーブル

| カラム名 | 型 | PRIMARY KEY | UNIQUE KEY | NOT NULL | FOREIGN KEY |
|-----------|----|-------------|------------|----------|-------------|
| id | unsigned bigint | ○ |  | ○ |  |
| user_id | unsigned bigint |  |  | ○ | users(id) |
| work_date | date |  | ○ (user_id + work_date) | ○ |  |
| clock_in | time |  |  |  |  |
| clock_out | time |  |  |  |  |
| break_in | time |  |  |  |  |
| break_out | time |  |  |  |  |
| break2_in | time |  |  |  |  |
| break2_out | time |  |  |  |  |
| total_work_time | time |  |  |  |  |
| total_break_time | time |  |  |  |  |
| comment | text |  |  | ○ |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

---

## correction_requestsテーブル

| カラム名 | 型 | PRIMARY KEY | UNIQUE KEY | NOT NULL | FOREIGN KEY |
|-----------|----|-------------|------------|----------|-------------|
| id | unsigned bigint | ○ |  | ○ |  |
| attendance_id | unsigned bigint |  |  | ○ | attendances(id) |
| user_id | unsigned bigint |  |  | ○ | users(id) |
| comment | text |  |  | ○ |  |
| new_date | date |  |  | ○ |  |
| new_clock_in | time |  |  |  |  |
| new_clock_out | time |  |  |  |  |
| new_break_in | time |  |  |  |  |
| new_break_out | time |  |  |  |  |
| new_break2_in | time |  |  |  |  |
| new_break2_out | time |  |  |  |  |
| application_date | date |  |  | ○ |  |
| status | enum('pending','approved') |  |  | ○ |  |
| created_at | timestamp |  |  |  |  |
| updated_at | timestamp |  |  |  |  |

---

### 補足

- 機能要件（FN021：：休憩機能）には「休憩入り・休憩戻りボタンは1日に何回でも押下できる」と記載されていますが、UI 上では休憩は2回までしか表示されないため、今回は `attendances` テーブル内の `break_in` / `break_out` と `break2_in` / `break2_out` カラムで対応しています。

- 以前は `breaks_times` テーブルを作成していましたが、UI 制約に合わせてテーブルを統合し、`attendances` テーブル内で管理する方式に変更しました。


---

## URL🔗

- [開発環境 : http://localhost/](http://localhost/)  
- [phpMyAdmin : http://localhost:8080/](http://localhost:8080/)
