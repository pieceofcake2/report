<?php
declare(strict_types=1);

namespace PieceOfCake2\Report\Test;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use PieceOfCake2\Report\Cover;
use PieceOfCake2\Report\Environment;
use PieceOfCake2\Report\Translator;

class CoverTest extends TestCase
{
    public function testTarget(): void
    {
        $environment = new Environment();
        $environment->repository = 'acme/app';
        $environment->branch = 'main';
        $environment->commit = '1234567';
        $environment->commitSubject = 'Fix the form';
        $environment->dirty = true;

        $rows = (new Cover(new Translator('ja')))->target($environment);

        $this->assertCount(3, $rows);
        $this->assertSame('リポジトリ', $rows[0]->label);
        $this->assertSame('acme/app', $rows[0]->value);
        $this->assertSame('1234567', $rows[2]->mono);
        $this->assertSame('Fix the form', $rows[2]->value);
        $this->assertSame('コミットしていない変更あり', $rows[2]->warning);
    }

    public function testTargetWithoutGit(): void
    {
        $this->assertSame([], (new Cover(new Translator()))->target(new Environment()));
    }

    public function testRun(): void
    {
        $zone = new DateTimeZone('Asia/Tokyo');
        $environment = new Environment();
        $environment->run = 'GitHub Actions CI #12';
        $environment->runUrl = 'https://github.com/acme/app/actions/runs/1';

        $rows = (new Cover(new Translator('ja')))->run(
            new DateTimeImmutable('2026-10-17 10:00:00', $zone),
            new DateTimeImmutable('2026-10-17 10:02:14', $zone),
            134.4,
            $environment,
        );

        $this->assertSame('2026-10-17 10:00:00 〜 10:02:14（Asia/Tokyo）', $rows[0]->value);
        $this->assertSame('2分14秒', $rows[1]->value);
        $this->assertSame('GitHub Actions CI #12', $rows[2]->value);
        $this->assertSame('https://github.com/acme/app/actions/runs/1', $rows[2]->sub);
    }

    public function testRunOverTwoDaysOnALocalMachine(): void
    {
        $environment = new Environment();
        $environment->os = 'macOS 15.1 arm64';

        $rows = (new Cover(new Translator()))->run(
            new DateTimeImmutable('2026-10-17 23:59:00'),
            new DateTimeImmutable('2026-10-18 00:01:00'),
            120,
            $environment,
        );

        $this->assertStringContainsString('2026-10-17 23:59:00 – 2026-10-18 00:01:00', $rows[0]->value);
        $this->assertSame('Local (macOS 15.1 arm64)', $rows[2]->value);
    }

    public function testDuration(): void
    {
        $cover = new Cover(new Translator());

        $this->assertSame('5s', $cover->duration(4.6));
        $this->assertSame('2m 14s', $cover->duration(134));
        $this->assertSame('1h 02m 03s', $cover->duration(3723));
    }

    public function testHeaders(): void
    {
        $environment = new Environment();
        $environment->commit = 'abcdef1';

        $this->assertSame('Report　acme/app', Cover::headerLeft('Report', 'acme/app'));
        $this->assertSame('Report', Cover::headerLeft('Report', null));
        $this->assertSame('Run on 2026-10-17 10:00　abcdef1', (new Cover(new Translator()))->headerRight(new DateTimeImmutable('2026-10-17 10:00:00'), $environment));
        $this->assertSame('Run on 2026-10-17 10:00', (new Cover(new Translator()))->headerRight(new DateTimeImmutable('2026-10-17 10:00:00'), new Environment()));
    }

    public function testPackage(): void
    {
        $this->assertStringStartsWith('phpunit/phpunit ', (string)Cover::package('phpunit/phpunit'));
        $this->assertNull(Cover::package('acme/not-installed'));
        $this->assertSame('pieceofcake2/report', Cover::rootPackage());
    }
}
