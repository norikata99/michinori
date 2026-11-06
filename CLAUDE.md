# みちのり（Michinori）

予定と実績のズレが見える目標管理アプリ。リポジトリ名は `michinori`。

## 概要
個人開発のプロジェクト。資格取得などの短期・中期ゴールに向けたタスク管理アプリで、予定と実績を記録し、そのズレを可視化する。

**重点はインフラ**（AWS / Terraform / CI/CD / 監視）の設計・構築・運用に置く。
アプリの機能は小さく保ち、インフラを厚くする。「なぜこの構成にしたか」を説明できることがゴール。

設計の詳細と決定事項は `docs/design.md` を参照。作業前に必ず読むこと。

## 技術スタック
- アプリ：Laravel（`src/`）、nginx + php-fpm、MySQL
- ローカル：Docker Compose
- 本番：AWS（ECS Fargate、RDS for MySQL、ALB、Route 53、ACM）
- IaC：Terraform（`infra/`）
- CI/CD：GitHub Actions（AWS認証はOIDC）
- バージョン：未決定。着手時に決めてここに追記する

## ディレクトリ構成
```
src/                 Laravel本体
docker/              nginx・php-fpmのDockerfile（マルチステージ）
compose.yaml         ローカル開発用
infra/bootstrap/     tfstate用S3（最初に一度だけ）
infra/modules/       network / ecs / rds / alb など
infra/envs/prod/     実際にデプロイする環境
.github/workflows/   app.yml（src/・docker/変更時）、infra.yml（infra/変更時）
docs/design.md       設計と決定事項
docs/adr/            設計判断の記録
```

## 作業ルール
- 1つのPRは1つの目的に絞り、小さく保つ。PRの説明には「何を・なぜ」を書く
- 設計判断を伴う変更をしたら `docs/adr/` にADRを追加する
- `docs/design.md` の「スコープ外」にある機能を勝手に追加しない
- 次の操作は実行前に必ず確認を取る：
  - `terraform apply` / `terraform destroy`
  - AWSリソースを作成・変更・削除するコマンド
  - DBの削除・初期化（`migrate:fresh` など）
  - `git push --force`
- **このリポジトリはパブリック**。コミットした内容は履歴ごと誰でも見られる前提で扱う
  - 秘密情報（`.env`、`*.tfvars`、認証情報、鍵）はコミットしない。消しても履歴に残るため、疑わしい場合はコミット前に止めて確認する
  - AWSのアカウントIDなどの環境固有の値はコードに直接書かず、変数や出力から参照する
  - OIDCの信頼ポリシーは、このリポジトリかつmainブランチまたは特定のEnvironmentに限定する。条件を緩めない
- AWSの料金やサービス仕様、ライブラリのバージョンなど変わりうる情報は、推測で書かず、確認が必要であることを明示する
- 説明は日本語で行う

## コマンド
（環境構築後に追記する）
