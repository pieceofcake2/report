<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

use RuntimeException;

/**
 * Writes a report to a PDF file: renders its HTML and prints it with Chrome.
 */
final class ReportWriter
{
    /**
     * Constructor
     *
     * @param \PieceOfCake2\Report\PdfPrinter|null $printer Printer, default: with the settings of the environment
     * @param \PieceOfCake2\Report\HtmlRenderer|null $renderer Renderer
     */
    public function __construct(private ?PdfPrinter $printer = null, private ?HtmlRenderer $renderer = null)
    {
    }

    /**
     * Writes the report
     *
     * @param \PieceOfCake2\Report\Document $doc Report
     * @param string $pdf Path of the PDF file
     * @param string|null $dir Directory of the HTML file, where the files that the bodies of the items link to (e.g.
     *  images) are, default: a temporary directory that is removed after printing
     * @return void
     * @throws \RuntimeException When the report cannot be written
     */
    public function write(Document $doc, string $pdf, ?string $dir = null): void
    {
        $html = ($this->renderer ?? new HtmlRenderer())->render($doc);
        $temporary = $dir === null;
        $dir ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'report-' . bin2hex(random_bytes(6));
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Cannot create the directory: %s', $dir));
        }
        $file = $dir . DIRECTORY_SEPARATOR . 'report.html';
        try {
            if (file_put_contents($file, $html) === false) {
                throw new RuntimeException(sprintf('Cannot write the HTML of the report: %s', $file));
            }
            ($this->printer ?? PdfPrinter::fromEnv($_SERVER))->print($file, $pdf);
        } finally {
            if (is_file($file)) {
                unlink($file);
            }
            if ($temporary && is_dir($dir)) {
                rmdir($dir);
            }
        }
    }
}
