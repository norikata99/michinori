# 0002. 採用するバージョンと、バージョンの固定方針

- ステータス：採用
- 日付：2026-11-07

## 背景
Laravelの雛形とDockerfileを作る前に、言語・フレームワーク・DB・ツールのバージョンを決める必要がある。

判断の基準は、開発期間（約1ヶ月）と、その後に運用を続ける期間の両方で、サポートが切れないこと。また、ローカルのDockerと本番のAWSで、メジャーバージョンを揃える。

サポート期間は2026-11-07時点で調べた。変わることがあるため、更新するときは各公式ページで確認し直す。

## 決定1：採用するバージョン

| 対象 | バージョン | 理由 |
|---|---|---|
| PHP | 8.5 | 8.4はアクティブサポートが2026-12-31に終わる。8.5はアクティブサポートが2027-12-31まで、セキュリティ修正が2029-12-31まで |
| Laravel | 13 | PHP 8.3〜8.5に対応。セキュリティ修正は2028年3月ごろまで。Laravel 12よりサポートが長い |
| MySQL | 8.4（LTS） | RDSでは8.0の標準サポートが2026-07-31に終わっている。8.4はRDSの標準サポートが2029-07-31まで |
| Node.js | 26（LTS） | フロントエンド（Vite）のビルドだけに使う。2026年10月にLTSになり、2029年4月までサポートされる見込み |
| Terraform | 1.16系 | S3のネイティブロック（`use_lockfile`）は1.11でGAになった。今サポートされている1.16系を使う |

### PHP 8.5で問題が出た場合
8.5はリリースから1年ほどのため、拡張機能やXdebugが対応していないことがある。Docker環境の構築で問題が出た場合は8.4に下げ、このADRを更新する。

### tfstateの管理
S3バックエンドを使い、状態のロックはS3のネイティブロック（`use_lockfile = true`）にする。

- DynamoDBでのロックは、Terraformのドキュメントで非推奨とされている
- ロック用のDynamoDBテーブルが要らないため、`infra/bootstrap/` で作るリソースが減る

## 決定2：バージョンの固定方針
**ビルドの材料はマイナーバージョンまで固定し、ロックファイルで中身を固める。ビルドしてできたイメージは、コミットSHAのタグで固定する。**

- 材料のパッチバージョン（セキュリティ修正）は、ビルドのたびに取り込まれる
- 本番で動いているイメージは、ECRのタグ（gitのコミットSHA、上書き禁止）で特定できる。ロールバックは1つ前のSHAに戻せばよい

### 部品ごとの固定方法

| 部品 | 固定の仕方 | ロックファイル |
|---|---|---|
| PHPのベースイメージ | `php:8.5-fpm-<Debianのコード名>` のように、マイナーとOSを指定する | — |
| PHPのパッケージ | `composer.json` に `"php": "^8.5"`、`"laravel/framework": "^13.0"` | `composer.lock` をコミット。ビルドでは `composer install` を使う |
| Node.js | ビルド用ステージで `node:26-<OS>`。`package.json` の `engines` と `.nvmrc` に26を書く | `package-lock.json` をコミット。ビルドでは `npm ci` を使う |
| MySQL（ローカル） | `mysql:8.4` | — |
| MySQL（RDS） | `engine_version = "8.4"`（メジャーまで）、`auto_minor_version_upgrade = true` | — |
| Terraform本体 | `required_version = "~> 1.16.0"`。CIでも1.16系を指定する | — |
| AWSプロバイダ | メジャーを固定する（例：`~> 6.0`。現在のメジャーは使い始めるときに確認する） | `.terraform.lock.hcl` をコミット |
| GitHub Actions | 使うActionをコミットSHAで固定する | — |

### 補足
- **OSも指定する理由**：OSを指定しないと、ベースのDebianが新しくなったときにOSが勝手に切り替わり、拡張機能のビルドが壊れることがある。実際に使うタグが存在するかは、Docker環境の構築時に確認する
- **RDSにパッチまで書かない理由**：RDSはマイナーバージョンごとにサポート終了日があり、終了すると自動でアップグレードされる（例：8.4.7は2026-11-30に終了）。Terraformに `8.4.7` のように書くと、AWSが上げた後の `plan` で元に戻す差分が出てしまう。ローカルとRDSでマイナーバージョンが少しずれることは許容する
- **Terraformのロックファイル**：手元はmacOS、CIはLinuxで動くため、両方のハッシュを入れる（`terraform providers lock -platform=darwin_arm64 -platform=linux_amd64`）
- **ActionsをSHAで固定する理由**：タグは後から別のコミットに付け替えられる。このリポジトリはパブリックで、OIDCでAWSを操作するため、意図しないコードが実行されるリスクを減らす
- **Node.jsは本番のイメージに入れない**：ビルドしたCSS・JSだけをnginxのイメージにコピーする

## 検討した代替案
- **パッチやdigestまで固定する**：再現性は最も高いが、セキュリティ修正のたびに手で更新する必要がある。成果物をSHAで固定していれば、本番の再現性は確保できるため採用しない
- **メジャーだけ固定する**（例：`php:8`）：更新の手間はないが、マイナーバージョンが勝手に上がって挙動が変わる恐れがあるため採用しない
- **PHP 8.4**：情報が多く問題が起きにくいが、アクティブサポートが間もなく終わるため、8.5を優先した

## 結果
- パッチの更新は自動で入るが、マイナー以上の更新は意図して行う必要がある
- 固定したバージョンは放っておくと古くなる。CIを整えた後（3週目以降）に、Dependabotで composer・npm・Docker・Terraform・GitHub Actions の更新をPRとして受け取ることを検討する
- PHPを8.4に下げた場合は、8.4のセキュリティサポートが終わる2028-12-31より前に、8.5以降へ上げる

## 参考
- [PHP Supported Versions](https://www.php.net/supported-versions.php)
- [MySQL on Amazon RDS versions](https://docs.aws.amazon.com/AmazonRDS/latest/UserGuide/MySQL.Concepts.VersionMgmt.html)
- [Laravel Support Policy](https://laravel.com/docs/releases#support-policy)
- [Node.js Releases](https://nodejs.org/en/about/previous-releases)
- [Terraform Backend Type: s3](https://developer.hashicorp.com/terraform/language/backend/s3)
