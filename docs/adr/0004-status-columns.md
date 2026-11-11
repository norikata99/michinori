# 0004. 状態の列は「文字列 + CHECK 制約 + PHP の enum」で持つ

- ステータス：採用
- 日付：2026-11-11

## 背景
`goals.status`（進行中 / 達成 / 中止）のように、決まった値だけを取る列がある。`tasks.status` も同じ形になる予定。

MySQL には ENUM 型がある。一方、Laravel では PHP の enum（`App\Enums\GoalStatus`）を使って、列の値をモデルで型付きのものとして扱える。

アプリ側のチェックしかないと、想定外の値が DB に入ることがある（SQL を直接流す、アプリのバグなど）。その場合、enum への変換でエラーになり、その行を含む画面全体が 500 になる。

## 決定
- 列は文字列（`VARCHAR`）で持つ
- 値の意味と一覧は PHP の enum にまとめる。モデルでは enum にキャストし、入力は `Rule::enum()` でチェックする
- DB 側でも値を制限するため、MySQL では CHECK 制約を付ける（例：`goals_status_check`）
  - Laravel のスキーマビルダーは CHECK 制約を作れないため、マイグレーションで SQL を流す
  - 制約の値はマイグレーションに直接書く。enum から組み立てると、enum を変えたときに過去のマイグレーションの意味が変わってしまう
  - 値を足すときは、制約を `DROP CHECK` して作り直すマイグレーションを書く
- テストで使う SQLite には制約を付けない（`ALTER TABLE` で制約を足せないため）。制約のテストは MySQL のときだけ動き、SQLite では skip する

## 理由
- **ENUM 型の落とし穴を避ける**
  - 並べ替えは値の文字列ではなく、定義した順になる
  - strict モードが無効だと、決まっていない値を入れてもエラーにならず、特別な空文字（番号 0）が入る
  - 末尾に値を足すだけならテーブルを作り直さずに済むが、途中に値を挟むと番号が振り直され、テーブルのコピーが必要になる
- **値の一覧を1か所にまとめる**：ENUM 型だと、値の一覧を DB の列定義と PHP の enum の2か所で持つことになる。文字列なら、意味は enum にまとまり、DB の CHECK 制約は「入ってよい値の範囲」だけを受け持つ
- **テストと本番で同じように扱える**：テストは SQLite、本番は MySQL で動かしている。文字列なら、どちらでも同じ型として読み書きできる

ENUM 型を避ける理由は「値を足すときに列の定義を変える必要がある」ことではない。CHECK 制約を付ける以上、値を足すときは制約の変更が要り、手間は同じくらいになる。

## 検討した他の案
- **MySQL の ENUM 型**：上の落とし穴があるため採らない
- **文字列だけ（DB で制限しない）**：想定外の値が入ると画面が 500 になるため採らない
- **値の一覧を別テーブルにして外部キーで参照する**：値が3〜4個で、増えることもまれなため、テーブルを増やすほどではない

## 影響
- SQLite のテストでは、CHECK 制約が効いていることを確かめられない。MySQL でテストを流す仕組み（#10 で検討）ができれば、制約のテストも自動で流れる
- 確認したこと：MySQL 8.4.13 で、制約に合わない値の INSERT が `ERROR 3819 (HY000): Check constraint 'goals_status_check' is violated.` で失敗する

## 参考
- [MySQL 8.4: CHECK Constraints](https://dev.mysql.com/doc/refman/8.4/en/create-table-check-constraints.html)
- [MySQL 8.4: The ENUM Type](https://dev.mysql.com/doc/refman/8.4/en/enum.html)
- [MySQL 8.4: Online DDL Operations（Column Operations）](https://dev.mysql.com/doc/refman/8.4/en/innodb-online-ddl-operations.html)
