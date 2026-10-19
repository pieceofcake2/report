# CHANGELOG

## 1.0.3

- `Environment` no longer blocks on a large error output of Git: it waited for the output while Git waited to
  write its errors
- When Chrome does not print in time, its helper processes are stopped too, and Chrome is killed when it does not
  end, so that no process keeps writing to the directory of Chrome while it is removed
- An error when the directory of Chrome cannot be created

## 1.0.2

- `ReportWriter` writes the HTML to a temporary directory that only the user can read (`0700`), and under a name
  of its own in the `$dir` given, so that a `report.html` there is not overwritten
- Documentation: `Item::$body` and `Document::$css` are raw HTML and CSS

## 1.0.1

- The row under the headings of a list has its border again: the border of the last row was removed for the row
  of a `thead` too
- The rows of the tables are 28px, a multiple of 3pt, so that their borders look the same at 100% in a PDF
  viewer: Chrome puts the borders on whole CSS pixels, and rows of 29.33px made every other border look thicker
- Headings break between words, spaces and zero width spaces (U+200B) only, or anywhere when a word does not
  fit, so that a Japanese title can break at a place that it marks

## 1.0.0

- First release: the report of pieceofcake2/panther as a package of its own. `Document` (cover, list and item
  pages), `HtmlRenderer`, `PdfPrinter` (the command line of a headless Chrome, without chromedriver),
  `ReportWriter`, `Cover`, `Environment` and `Translator` (English and Japanese)
