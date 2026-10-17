<?php
declare(strict_types=1);

namespace PieceOfCake2\Report\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PieceOfCake2\Report\Translator;

class TranslatorTest extends TestCase
{
    private string $dir;

    public function setUp(): void
    {
        parent::setUp();
        $this->dir = TMP . 'locales-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    public function tearDown(): void
    {
        array_map('unlink', glob($this->dir . DS . '*') ?: []);
        rmdir($this->dir);
        parent::tearDown();
    }

    public function testBundledLanguages(): void
    {
        $this->assertSame('PASSED', (new Translator())->translate('verdict.passed'));
        $this->assertSame('合格', (new Translator('ja'))->translate('verdict.passed'));
        $this->assertSame('ja', (new Translator('ja'))->language());
    }

    public function testBundledLanguagesHaveTheSameMessages(): void
    {
        $en = json_decode((string)file_get_contents(Translator::LOCALE_DIR . DS . 'en.json'), true);
        $ja = json_decode((string)file_get_contents(Translator::LOCALE_DIR . DS . 'ja.json'), true);

        $this->assertIsArray($en);
        $this->assertIsArray($ja);
        $this->assertSame(array_keys($en), array_keys($ja));
    }

    public function testPlaceholders(): void
    {
        $translator = new Translator('ja');

        $this->assertSame('2分14秒', $translator->translate('duration.minutes', ['minutes' => 2, 'seconds' => 14]));
        $this->assertSame('{start} 〜 1（{timezone}）', $translator->translate('date.range', ['end' => 1]));
        $this->assertSame('unknown.key', $translator->translate('unknown.key'));
    }

    public function testLocaleDirsAddMessages(): void
    {
        file_put_contents($this->dir . DS . 'en.json', json_encode(['title' => 'Security Report']));
        file_put_contents($this->dir . DS . 'ja.json', json_encode(['title' => 'セキュリティ診断報告書']));

        $this->assertSame('Security Report', (new Translator('en', [$this->dir]))->translate('title'));
        $this->assertSame('セキュリティ診断報告書', (new Translator('ja', [$this->dir]))->translate('title'));
        $this->assertSame('合格', (new Translator('ja', [$this->dir]))->translate('verdict.passed'));
    }

    public function testLaterLocaleDirsWin(): void
    {
        $other = $this->dir . DS . 'other';
        mkdir($other);
        file_put_contents($this->dir . DS . 'ja.json', json_encode(['title' => '一つ目', 'verdict.passed' => 'OK']));
        file_put_contents($other . DS . 'ja.json', json_encode(['title' => '二つ目']));
        try {
            $translator = new Translator('ja', [$this->dir, null, $other]);

            $this->assertSame('二つ目', $translator->translate('title'));
            $this->assertSame('OK', $translator->translate('verdict.passed'));
        } finally {
            unlink($other . DS . 'ja.json');
            rmdir($other);
        }
    }

    public function testAnotherLanguageFallsBackToEnglish(): void
    {
        file_put_contents($this->dir . DS . 'fr.json', json_encode(['verdict.passed' => 'RÉUSSI']));

        $translator = new Translator('fr', [$this->dir]);

        $this->assertSame('RÉUSSI', $translator->translate('verdict.passed'));
        $this->assertSame('FAILED', $translator->translate('verdict.failed'));
    }

    public function testUnknownLanguage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No messages for the report language "fr"');

        new Translator('fr', [$this->dir]);
    }

    public function testInvalidLanguage(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Translator('../en');
    }

    public function testInvalidJson(): void
    {
        file_put_contents($this->dir . DS . 'fr.json', '{');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON');

        new Translator('fr', [$this->dir]);
    }
}
