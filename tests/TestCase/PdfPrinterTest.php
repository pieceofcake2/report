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

    public function testTimeoutStopsTheHelpersOfChrome(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('The fake Chrome is a shell script.');
        }
        // A Chrome that never prints, with a helper that keeps writing to its profile
        $chrome = TMP . 'slow-chrome';
        $pids = TMP . 'slow-chrome.pids';
        file_put_contents($chrome, "#!/bin/sh\nfor arg; do case \"\$arg\" in --user-data-dir=*) dir=\"\${arg#--user-data-dir=}\";; esac; done\nmkdir -p \"\$dir\"\n"
            . "( while true; do date > \"\$dir/helper.log\"; sleep 0.05; done ) &\necho \$! > " . escapeshellarg($pids) . "\nsleep 30\n");
        chmod($chrome, 0755);
        file_put_contents(TMP . 'slow.html', '<p>Report</p>');
        $before = glob(sys_get_temp_dir() . DS . 'report-chrome-*') ?: [];

        try {
            (new PdfPrinter($chrome, false, 1))->print(TMP . 'slow.html', TMP . 'slow.pdf');
            $this->fail('Chrome printed nothing.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Chrome did not print the report', $e->getMessage());
        }

        // The helper is stopped, and the directory of Chrome removed
        $helper = (int)trim((string)file_get_contents($pids));
        exec('kill -0 ' . $helper . ' 2>/dev/null', $output, $code);
        $this->assertNotSame(0, $code, 'The helper of Chrome still runs');
        $this->assertSame($before, glob(sys_get_temp_dir() . DS . 'report-chrome-*') ?: []);
    }
}
