# Report Documentation

A4 PDF reports for the tools of [pieceofcake2](https://github.com/pieceofcake2): a cover with the result, a list
of the items by group, and a page for each item. The HTML of the report is printed by the command line of a
headless Chrome, with no other dependency.

It is the report of [pieceofcake2/panther](https://pieceofcake2.github.io/panther/) (End-to-End tests), and of
the security checks of pieceofcake2.

## Requirements

- PHP 8.0+
- Google Chrome or Chromium 131+ (the header and the page numbers use CSS page margin boxes)
- Fonts of the languages of the report: on Linux, `fonts-noto-cjk` for Japanese

## Installation

```bash
composer require pieceofcake2/report
```

## Writing a Report

A `Document` holds everything that the report shows; `ReportWriter` renders it to HTML and prints it to PDF:

```php
use PieceOfCake2\Report\Count;
use PieceOfCake2\Report\Document;
use PieceOfCake2\Report\Group;
use PieceOfCake2\Report\Item;
use PieceOfCake2\Report\ReportWriter;
use PieceOfCake2\Report\Verdict;

$doc = new Document(
    title: 'Inspection Report',
    verdict: new Verdict(true, 'PASSED', [new Count(1, 'Items'), new Count(1, 'Passed', Count::PASS)]),
    columns: ['No.', 'Item', 'Result'],
    groups: [new Group(1, 'Contact form', [new Item('1-1', 'Sends the message', 'pass', 'Passed')])],
    listTitle: 'Items',
);

(new ReportWriter())->write($doc, 'tmp/report.pdf');
```

[docs/examples/report.php](examples/report.php) writes a complete sample (`php docs/examples/report.php tmp/sample.pdf ja`).

| Class | |
| --- | --- |
| `Document` | The title, the project, the verdict, the target and run tables, a note, the headings, the header of the pages, extra CSS and the language |
| `Verdict`, `Count` | The result on the cover (green or red) and the numbers beside it. `Count::PASS` and `Count::FAIL` color a number |
| `Group`, `Item` | The items by group. An item is a row of the list and a page with a badge, a table (`meta`), a description, a `Message` and a body of HTML |
| `Row` | A row of a table: a label, a value, and optionally a value in a monospaced font, a second line or a warning |
| `Cover` | The common rows: repository, branch and commit (`target()`), date, duration and environment (`run()`), and the header of the pages |
| `Environment` | The repository, branch and commit, and the run of GitHub Actions or the local machine (`Environment::detect()`) |
| `Translator` | The messages in English and Japanese (`resources/locales/`), and the directories of more messages |
| `HtmlRenderer`, `PdfPrinter`, `ReportWriter` | The HTML, the printing with Chrome, and both |

The badges of the bundled CSS are `pass` (green), `fail` (red) and `skip` (yellow). Add classes for other
badges, or the styles of the bodies of the items, with `Document::$css`.

## Languages

`Translator` reads the common messages of this package, then those of the directories you give, which add
messages, languages, or replace messages. A message that a language does not have is the English one.

```php
$translator = new Translator('ja', [__DIR__ . '/resources/locales', getenv('MY_LOCALE_DIR') ?: null]);
$translator->translate('verdict.passed'); // 合格
```

## Printing

`PdfPrinter` runs Chrome with `--headless --print-to-pdf`: the page size and margins come from the CSS
(`@page`), backgrounds are printed, Chrome adds no header or footer of its own, and the headings become the
bookmarks of the PDF. It uses a profile of its own, so a running Chrome is not disturbed.

| Environment variable | |
| --- | --- |
| `REPORT_CHROME_BINARY` | The Chrome binary. Default: `google-chrome`, `google-chrome-stable`, `chromium`, `chromium-browser` or `chrome` in the `PATH`, then the usual places on macOS and Windows |
| `REPORT_NO_SANDBOX` | `1` disables the sandbox of Chrome, e.g. in a container run as root |

On GitHub Actions, the Ubuntu runners have Google Chrome. Install a Japanese font for Japanese reports:

```yaml
- run: sudo apt-get install -y fonts-noto-cjk
```
