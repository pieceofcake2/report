<?php
declare(strict_types=1);

namespace PieceOfCake2\Report\Test;

use PHPUnit\Framework\TestCase;
use PieceOfCake2\Report\Document;
use PieceOfCake2\Report\PdfPrinter;
use PieceOfCake2\Report\ReportWriter;
use PieceOfCake2\Report\Verdict;

class ReportWriterTest extends TestCase
{
    /**
     * A Chrome that prints a PDF and records the HTML file it was given, and the mode of its directory
     *
     * @param string $log File of the record
     * @return \PieceOfCake2\Report\PdfPrinter
     */
    private static function printer(string $log): PdfPrinter
    {
        $chrome = TMP . 'fake-chrome';
        file_put_contents($chrome, "#!/bin/sh\nfor arg; do case \"\$arg\" in --print-to-pdf=*) printf '%%PDF-1.4' > \"\${arg#--print-to-pdf=}\";; esac; html=\"\$arg\"; done\n"
            . "file=\"\${html#file://}\"\nprintf '%s %s' \"\$file\" \"\$(stat -f %Lp \"\$(dirname \"\$file\")\" 2>/dev/null || stat -c %a \"\$(dirname \"\$file\")\")\" > " . escapeshellarg($log) . "\n");
        chmod($chrome, 0755);

        return new PdfPrinter($chrome);
    }

    public function testTemporaryDirectory(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('The fake Chrome is a shell script.');
        }
        $log = TMP . 'chrome.log';
        $pdf = TMP . 'writer' . DS . 'report.pdf';

        (new ReportWriter(self::printer($log)))->write(new Document('Report', new Verdict(true, 'PASSED'), []), $pdf);

        [$html, $mode] = explode(' ', (string)file_get_contents($log));
        // Only the user can read it, and it is removed after printing
        $this->assertSame('700', $mode);
        $this->assertFileDoesNotExist($html);
        $this->assertDirectoryDoesNotExist(dirname($html));
        $this->assertStringStartsWith('%PDF', (string)file_get_contents($pdf));
    }

    public function testDirectoryGiven(): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $this->markTestSkipped('The fake Chrome is a shell script.');
        }
        $dir = TMP . 'assets';
        is_dir($dir) || mkdir($dir);
        file_put_contents($dir . DS . 'report.html', 'mine');
        $log = TMP . 'chrome.log';

        (new ReportWriter(self::printer($log)))->write(new Document('Report', new Verdict(true, 'PASSED'), []), TMP . 'writer' . DS . 'report.pdf', $dir);

        // A name of its own, removed after printing; the files of the directory are kept
        [$html] = explode(' ', (string)file_get_contents($log));
        $this->assertSame(realpath($dir), realpath(dirname($html)));
        $this->assertFileDoesNotExist($html);
        $this->assertSame('mine', file_get_contents($dir . DS . 'report.html'));
    }
}
