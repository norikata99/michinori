# 0003. 認証は Breeze の Blade 版で入れ、Tailwind CSS は Breeze に合わせて v3 にする

- ステータス：採用
- 日付：2026-11-11

## 背景
設計（`docs/design.md` 2章）で、認証には Laravel Breeze を使うと決めている。Breeze には Blade、Livewire、React、Vue などの版がある。

Laravel 13 の雛形は Tailwind CSS v4（`@tailwindcss/vite`）で作られている。一方、Breeze の Blade 版は、入れると CSS の設定を Tailwind CSS v3（`tailwind.config.js`、PostCSS）に置き換える。

## 決定
- Breeze の **Blade 版**を使う。テストは雛形と同じ PHPUnit のまま
- Tailwind CSS は、Breeze が入れる **v3** の設定をそのまま使う。使わなくなった `@tailwindcss/vite` は `package.json` から外す

## 理由
- アプリは小さく保つ方針。画面はサーバー側の Blade だけで足り、React や Vue を入れるとビルドと依存が増える
- Breeze が作る画面とテストは、Breeze が入れる CSS の設定に合わせて作られている。v4 に書き換えると、Breeze の画面の見た目を自分で確認し続けることになる
- 画面の見た目は重点ではないので、ここに時間をかけない

## 検討した他の案
- **v4 に移行する**（Tailwind の移行ツールで Breeze の画面を書き換える）：雛形と揃うが、Breeze の更新を取り込むたびに差分が出る。必要になったら、別のPRで行う
- **Livewire 版**：動きのある画面は作らないため不要
- **認証を自前で書く**：Breeze で足りるため不要

## 影響
- `welcome.blade.php`（Laravel の初期画面）は v4 用のクラス（`starting:` など）を一部使っており、v3 では効かない。初期画面は後で作り直す前提なので、そのままにする
- Tailwind CSS v3 のサポート状況は変わりうるため、Dependabot などで依存を更新するときに確認する
