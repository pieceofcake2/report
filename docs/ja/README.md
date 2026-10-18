# Report ドキュメント

[pieceofcake2](https://github.com/pieceofcake2) のツールが出す、A4 の PDF の報告書です。結果を載せた表紙、グループごとの
項目の一覧、項目ごとのページでできています。報告書の HTML は、ヘッドレスの Chrome のコマンドラインで印刷するので、
ほかに依存するものはありません。

[pieceofcake2/panther](https://pieceofcake2.github.io/panther/ja/)（End-to-End テスト）と、pieceofcake2 の
セキュリティ診断の報告書に使います。

## 必要なもの

- PHP 8.0 以上
- Google Chrome または Chromium 131 以上（ヘッダーとページ番号に CSS のページ余白ボックスを使います）
- 報告書の言語のフォント。Linux では、日本語に `fonts-noto-cjk`

## インストール

```bash
composer require pieceofcake2/report
```

## 報告書を書く

報告書に載せるものはすべて `Document` に入れます。`ReportWriter` が HTML にして、PDF に印刷します。

```php
use PieceOfCake2\Report\Count;
use PieceOfCake2\Report\Document;
use PieceOfCake2\Report\Group;
use PieceOfCake2\Report\Item;
use PieceOfCake2\Report\ReportWriter;
use PieceOfCake2\Report\Verdict;

$doc = new Document(
    title: '検査報告書',
    verdict: new Verdict(true, '合格', [new Count(1, '項目'), new Count(1, '成功', Count::PASS)]),
    columns: ['No.', '項目', '結果'],
    groups: [new Group(1, 'お問い合わせフォーム', [new Item('1-1', 'メッセージを送信する', 'pass', '成功')])],
    listTitle: '項目一覧',
    language: 'ja',
);

(new ReportWriter())->write($doc, 'tmp/report.pdf');
```

[docs/examples/report.php](../examples/report.php) は、すべての欄を使ったサンプルを書き出します（`php docs/examples/report.php tmp/sample.pdf ja`）。

| クラス | |
| --- | --- |
| `Document` | タイトル、プロジェクト、判定、対象と実行の表、注記、見出し、ページのヘッダー、追加の CSS、言語 |
| `Verdict`、`Count` | 表紙の結果（緑か赤）と、その横の数。`Count::PASS` と `Count::FAIL` で数に色を付けます |
| `Group`、`Item` | グループごとの項目。項目は一覧の 1 行と 1 ページで、バッジ、表（`meta`）、説明、`Message`、HTML の本文を持ちます |
| `Row` | 表の 1 行。ラベルと値、必要なら等幅の値、2 行目、警告 |
| `Cover` | 共通の行。リポジトリ、ブランチ、コミット（`target()`）と、日時、所要時間、実行環境（`run()`）、ページのヘッダー |
| `Environment` | リポジトリ、ブランチ、コミットと、GitHub Actions の実行またはローカルのマシン（`Environment::detect()`） |
| `Translator` | 英語と日本語のメッセージ（`resources/locales/`）と、追加のメッセージのディレクトリ |
| `HtmlRenderer`、`PdfPrinter`、`ReportWriter` | HTML、Chrome での印刷、その両方 |

同梱の CSS のバッジは `pass`（緑）、`fail`（赤）、`skip`（黄）です。ほかのバッジのクラスや、項目の本文のスタイルは、
`Document::$css` で足します。

`Item::$body` と `Document::$css` は、エスケープせずにそのまま HTML に書きます。信頼できる値から組み立て、テストや
スキャンの値は `htmlspecialchars()` などでエスケープしてください。そのほかのテキストは `HtmlRenderer` がエスケープします。

`ReportWriter::write()` は、ユーザーだけが読める一時ディレクトリに HTML を書き、印刷したあとに消します。本文が参照する
ファイル (画像など) のために `$dir` 引数を指定すると、そこに専用の名前で HTML を書き、印刷したあとに消します。

## 言語

`Translator` は、このパッケージの共通のメッセージを読み、続けて指定したディレクトリのメッセージを読みます。指定した
ディレクトリで、メッセージや言語を足したり、メッセージを置き換えたりできます。その言語にないメッセージは英語になります。

```php
$translator = new Translator('ja', [__DIR__ . '/resources/locales', getenv('MY_LOCALE_DIR') ?: null]);
$translator->translate('verdict.passed'); // 合格
```

## 印刷

`PdfPrinter` は、Chrome を `--headless --print-to-pdf` で実行します。用紙のサイズと余白は CSS（`@page`）で決まり、
背景も印刷され、Chrome 自身のヘッダーとフッターは付かず、見出しが PDF のしおりになります。専用のプロファイルを使うので、
起動中の Chrome には影響しません。

| 環境変数 | |
| --- | --- |
| `REPORT_CHROME_BINARY` | Chrome の実行ファイル。デフォルトは、`PATH` の `google-chrome`、`google-chrome-stable`、`chromium`、`chromium-browser`、`chrome`、次に macOS と Windows の通常の場所 |
| `REPORT_NO_SANDBOX` | `1` で Chrome のサンドボックスを無効にします。root で動くコンテナなどで使います |

GitHub Actions の Ubuntu のランナーには Google Chrome が入っています。日本語の報告書には、日本語のフォントを入れてください。

```yaml
- run: sudo apt-get install -y fonts-noto-cjk
```
