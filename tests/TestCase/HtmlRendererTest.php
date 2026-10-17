<?php
declare(strict_types=1);

namespace PieceOfCake2\Report\Test;

use PHPUnit\Framework\TestCase;
use PieceOfCake2\Report\Count;
use PieceOfCake2\Report\Document;
use PieceOfCake2\Report\Group;
use PieceOfCake2\Report\HtmlRenderer;
use PieceOfCake2\Report\Item;
use PieceOfCake2\Report\Message;
use PieceOfCake2\Report\Row;
use PieceOfCake2\Report\Verdict;

class HtmlRendererTest extends TestCase
{
    private static function document(array $columns = ['No.', 'Test', 'Result', 'Time'], string $css = ''): Document
    {
        return new Document(
            title: 'Test <Report>',
            verdict: new Verdict(false, 'FAILED', [new Count(2, 'Tests'), new Count(1, 'Failed', Count::FAIL)]),
            columns: $columns,
            groups: [
                new Group(1, 'Contact form', [
                    new Item('1-1', 'Sends the message', 'pass', 'Passed', '1.2s', [new Row('Test', 'ContactsTest::testSend', monoValue: true)], "Line 1\nLine 2"),
                    new Item('1-2', 'Shows the errors', 'fail', 'Failed', '0.4s', message: new Message('Failure', 'Expected <b>'), body: '<figure class="shot">image</figure>'),
                ]),
            ],
            project: 'acme/app',
            target: [new Row('Commit', 'Fix the form', mono: '1234567', warning: 'with uncommitted changes')],
            run: [new Row('Environment', 'GitHub Actions CI #12', sub: 'https://github.com/acme/app/actions/runs/1')],
            note: 'The screenshots show test data.',
            listTitle: 'Tests',
            targetTitle: 'Target',
            runTitle: 'Run',
            headerLeft: 'Test "Report"',
            headerRight: 'Run on 2026-10-17',
            css: '.shot { color: red; }',
            language: 'en',
        );
    }

    public function testCover(): void
    {
        $html = (new HtmlRenderer())->render(self::document());

        $this->assertStringContainsString('<html lang="en">', $html);
        $this->assertStringContainsString('<h1>Test &lt;Report&gt;</h1>', $html);
        $this->assertStringContainsString('<div class="project">acme/app</div>', $html);
        $this->assertStringContainsString('<div class="verdict failed">', $html);
        $this->assertStringContainsString('<b class="t-fail">1</b><span>Failed</span>', $html);
        $this->assertStringContainsString('<span class="mono">1234567</span>', $html);
        $this->assertStringContainsString('<span class="warn">with uncommitted changes</span>', $html);
        $this->assertStringContainsString('<span class="mono sub">https://github.com/acme/app/actions/runs/1</span>', $html);
        $this->assertStringContainsString('<p class="note">The screenshots show test data.</p>', $html);
    }

    public function testStyleAndHeaders(): void
    {
        $html = (new HtmlRenderer())->render(self::document());

        $this->assertStringContainsString('size: A4;', $html);
        $this->assertStringContainsString('.shot { color: red; }', $html);
        $this->assertStringContainsString('@top-left { content: "Test \22 Report\22 "; }', $html);
        $this->assertStringContainsString('@top-right { content: "Run on 2026-10-17"; }', $html);
    }

    public function testListAndPages(): void
    {
        $html = (new HtmlRenderer())->render(self::document());

        $this->assertStringContainsString('<h3>1. Contact form</h3>', $html);
        $this->assertStringContainsString('<col class="time">', $html);
        $this->assertStringContainsString('<th>Time</th>', $html);
        $this->assertStringContainsString('<td><a href="#i1-2">Shows the errors</a></td>', $html);
        $this->assertStringContainsString('<td class="num">0.4s</td>', $html);
        $this->assertStringContainsString('<section class="item" id="i1-1">', $html);
        $this->assertStringContainsString('<tr><th>Test</th><td class="mono">ContactsTest::testSend</td></tr>', $html);
        $this->assertStringContainsString("<p class=\"desc\">Line 1<br />\nLine 2</p>", $html);
        $this->assertStringContainsString('<div class="message fail">', $html);
        $this->assertStringContainsString('<pre>Expected &lt;b&gt;</pre>', $html);
        $this->assertStringContainsString('<figure class="shot">image</figure>', $html);
    }

    public function testListWithoutTheExtraColumn(): void
    {
        $html = (new HtmlRenderer())->render(self::document(['No.', 'Finding', 'Risk']));

        $this->assertStringNotContainsString('<col class="time">', $html);
        $this->assertStringNotContainsString('class="num"', $html);
    }

    public function testCssString(): void
    {
        $this->assertSame('"a\5C b\22 \3C /style\3E "', HtmlRenderer::cssString('a\\b"</style>'));
    }
}
