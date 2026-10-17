# CHANGELOG

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
