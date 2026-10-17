<?php
declare(strict_types=1);

namespace PieceOfCake2\Report\Test;

use PHPUnit\Framework\TestCase;
use PieceOfCake2\Report\PdfPrinter;
use RuntimeException;

class PdfPrinterTest extends TestCase
{
    public function testFindChromeInThePath(): void
    {
        $dir = TMP . 'bin-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $chrome = $dir . DS . 'chromium';
        file_put_contents($chrome, "#!/bin/sh\n");
        chmod($chrome, 0755);
        try {
            $this->assertSame($chrome, PdfPrinter::findChrome('/nowhere' . PATH_SEPARATOR . $dir));
        } finally {
            unlink($chrome);
            rmdir($dir);
        }
    }

    public function testFromEnv(): void
    {
        $printer = PdfPrinter::fromEnv(['REPORT_CHROME_BINARY' => '/nowhere/chrome', 'REPORT_NO_SANDBOX' => '1']);
        $html = TMP . 'page.html';
        file_put_contents($html, '<p>page</p>');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot run Chrome');
        try {
            $printer->print($html, TMP . 'page.pdf');
        } finally {
            unlink($html);
        }
    }

    public function testPrint(): void
    {
        $this->skipIf(PdfPrinter::findChrome() === null && empty($_SERVER['REPORT_CHROME_BINARY']), 'Chrome is not installed.');

        $html = TMP . 'print.html';
        $pdf = TMP . 'out' . DS . 'print.pdf';
        file_put_contents($html, '<!doctype html><html><head><meta charset="utf-8"><style>@page { size: A4; }</style></head><body><h1>Page 1</h1></body></html>');
        try {
            PdfPrinter::fromEnv($_SERVER)->print($html, $pdf);

            $this->assertStringStartsWith('%PDF', (string)file_get_contents($pdf));
        } finally {
            foreach ([$html, $pdf] as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            if (is_dir(dirname($pdf))) {
                rmdir(dirname($pdf));
            }
        }
    }

    private function skipIf(bool $condition, string $message): void
    {
        if ($condition) {
            $this->markTestSkipped($message);
        }
    }
}
