<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use UnexpectedValueException;

/**
 * Prints an HTML file to PDF with the command line of a headless Chrome (`--print-to-pdf`).
 *
 * The page size and margins come from the CSS (`@page`), backgrounds are printed, the pages have no header or
 * footer of Chrome (the report has its own) and the headings become the bookmarks of the PDF. Chrome 131+ is
 * needed for the header and page numbers of the report (CSS page margin boxes).
 */
final class PdfPrinter
{
    /**
     * Chrome binaries looked for in the `PATH`, in order
     */
    public const COMMANDS = ['google-chrome', 'google-chrome-stable', 'chromium', 'chromium-browser', 'chrome'];

    /**
     * Chrome binaries looked for at their usual places, in order
     */
    public const PATHS = [
        '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
        '/Applications/Chromium.app/Contents/MacOS/Chromium',
        'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
    ];

    /**
     * Constructor
     *
     * @param string|null $binary Chrome binary, default: found in the `PATH` or at its usual places
     * @param bool $noSandbox Whether to disable the sandbox of Chrome, e.g. in a container run as root
     * @param int $timeout Seconds to wait for Chrome
     */
    public function __construct(
        private ?string $binary = null,
        private bool $noSandbox = false,
        private int $timeout = 120,
    ) {
    }

    /**
     * A printer with the settings of the environment variables `REPORT_CHROME_BINARY` and `REPORT_NO_SANDBOX`
     *
     * @param array<string, mixed> $env Environment variables, e.g. `$_SERVER`
     * @return self
     */
    public static function fromEnv(array $env): self
    {
        $binary = $env['REPORT_CHROME_BINARY'] ?? null;

        return new self(
            is_string($binary) && $binary !== '' ? $binary : null,
            filter_var($env['REPORT_NO_SANDBOX'] ?? false, FILTER_VALIDATE_BOOLEAN),
        );
    }

    /**
     * Prints the HTML file to the PDF file
     *
     * @param string $html Path of the HTML file
     * @param string $pdf Path of the PDF file
     * @return void
     * @throws \RuntimeException When Chrome is not found or cannot print the page
     */
    public function print(string $html, string $pdf): void
    {
        $binary = $this->binary ?? self::findChrome();
        if ($binary === null) {
            throw new RuntimeException('Chrome is needed to print the report: install it, or set REPORT_CHROME_BINARY.');
        }

        if (str_contains($binary, '/') || str_contains($binary, '\\')) {
            if (!is_file($binary) || !is_executable($binary)) {
                throw new RuntimeException(sprintf('Cannot run Chrome: %s is not an executable file.', $binary));
            }
        }

        $dir = dirname($pdf);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Cannot create the directory of the report: %s', $dir));
        }

        $work = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'report-chrome-' . bin2hex(random_bytes(6));
        if (!mkdir($work, 0700, true) && !is_dir($work)) {
            throw new RuntimeException(sprintf('Cannot create the directory of Chrome: %s', $work));
        }
        $output = $work . DIRECTORY_SEPARATOR . 'report.pdf';
        try {
            $command = [
                $binary,
                '--headless',
                '--disable-gpu',
                '--hide-scrollbars',
                '--no-first-run',
                '--no-default-browser-check',
                '--no-pdf-header-footer',
                '--generate-pdf-document-outline',
                // A profile of its own, so that a running Chrome or the browser of tests is not used
                '--user-data-dir=' . $work . DIRECTORY_SEPARATOR . 'profile',
                '--print-to-pdf=' . $output,
            ];
            if ($this->noSandbox) {
                $command[] = '--no-sandbox';
            }
            $command[] = self::fileUrl($html);

            [$status, $log] = $this->run($command);
            $data = is_file($output) ? (string)file_get_contents($output) : '';
            if (!str_starts_with($data, '%PDF')) {
                throw new RuntimeException(sprintf('Chrome did not print the report (exit code %s): %s', $status ?? 'none', trim($log)));
            }
            if (file_put_contents($pdf, $data) === false) {
                throw new RuntimeException(sprintf('Cannot write the report: %s', $pdf));
            }
        } finally {
            self::remove($work);
        }
    }

    /**
     * The Chrome binary in the `PATH` or at its usual places
     *
     * @param string|null $path The `PATH`, default: the one of this process
     * @return string|null
     */
    public static function findChrome(?string $path = null): ?string
    {
        $path ??= (string)getenv('PATH');
        foreach (self::COMMANDS as $command) {
            foreach (explode(PATH_SEPARATOR, $path) as $dir) {
                if ($dir === '') {
                    continue;
                }
                foreach (['', '.exe'] as $suffix) {
                    $file = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $command . $suffix;
                    if (is_file($file) && is_executable($file)) {
                        return $file;
                    }
                }
            }
        }
        foreach (self::PATHS as $file) {
            if (is_file($file) && is_executable($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Runs Chrome and waits for it
     *
     * @param array<string> $command Command and arguments
     * @return array{0: int|null, 1: string} Exit code (null after the timeout) and output
     */
    private function run(array $command): array
    {
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException(sprintf('Cannot run Chrome: %s', $command[0]));
        }
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $log = '';
        $deadline = microtime(true) + $this->timeout;
        do {
            $log .= (string)stream_get_contents($pipes[1]) . (string)stream_get_contents($pipes[2]);
            $status = proc_get_status($process);
            if (!$status['running']) {
                break;
            }
            usleep(50000);
        } while (microtime(true) < $deadline);

        $code = null;
        if ($status['running']) {
            self::stop($process, (int)$status['pid']);
        } else {
            $code = $status['exitcode'];
        }
        $log .= (string)stream_get_contents($pipes[1]) . (string)stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        return [$code, $log];
    }

    /**
     * Stops Chrome after its timeout: its helpers first, which would write to its profile while it is removed, then
     * Chrome, and kills what still runs after a few seconds
     *
     * @param resource $process Process of Chrome
     * @param int $pid Its process ID
     * @return void
     */
    private static function stop($process, int $pid): void
    {
        $helpers = [];
        if (PHP_OS_FAMILY !== 'Windows') {
            exec('pgrep -P ' . $pid . ' 2>/dev/null', $helpers);
            $helpers = array_values(array_filter(array_map('intval', $helpers)));
            foreach ($helpers as $helper) {
                exec('kill -TERM ' . $helper . ' 2>/dev/null');
            }
        }
        proc_terminate($process);
        // Until Chrome and its helpers have ended, then killed
        $running = static function () use ($process, $helpers): bool {
            if (proc_get_status($process)['running']) {
                return true;
            }
            foreach ($helpers as $helper) {
                exec('kill -0 ' . $helper . ' 2>/dev/null', $output, $code);
                if ($code === 0) {
                    return true;
                }
            }

            return false;
        };
        $deadline = microtime(true) + 5;
        while ($running() && microtime(true) < $deadline) {
            usleep(50000);
        }
        if ($running()) {
            foreach ($helpers as $helper) {
                exec('kill -KILL ' . $helper . ' 2>/dev/null');
            }
            proc_terminate($process, 9);
        }
    }

    /**
     * The `file:` URL of a local file
     *
     * @param string $path Path
     * @return string
     */
    private static function fileUrl(string $path): string
    {
        $path = str_replace('\\', '/', (string)realpath($path));

        return 'file://' . (str_starts_with($path, '/') ? '' : '/') . implode('/', array_map('rawurlencode', explode('/', $path)));
    }

    /**
     * Removes a directory and its files
     *
     * @param string $dir Directory
     * @return void
     */
    private static function remove(string $dir): void
    {
        // A few times: a process of Chrome that ends may still write a file
        $try = 0;
        while ($try++ < 5 && is_dir($dir)) {
            set_error_handler(static fn(): bool => true);
            try {
                $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    /** @var \SplFileInfo $file */
                    $file->isDir() && !$file->isLink() ? rmdir($file->getPathname()) : unlink($file->getPathname());
                }
                rmdir($dir);
            } catch (UnexpectedValueException) {
                // A directory removed meanwhile
            } finally {
                restore_error_handler();
            }
            if (is_dir($dir)) {
                usleep(100000);
            }
        }
    }
}
