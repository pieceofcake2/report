<?php
declare(strict_types=1);

namespace PieceOfCake2\Report\Test;

use PHPUnit\Framework\TestCase;
use PieceOfCake2\Report\Check;
use PieceOfCake2\Report\CheckList;
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

    public function testEmptyGroups(): void
    {
        $doc = self::document();
        $doc->groups = [new Group(1, 'High'), new Group(2, 'Medium', empty: 'No <alerts>'), $doc->groups[0]];
        $doc->groups[2]->no = 3;
        $html = (new HtmlRenderer())->render($doc);

        $this->assertStringContainsString('<h3>1. High</h3>', $html);
        $this->assertMatchesRegularExpression('#<h3>1\. High</h3>\s*<p class="empty">None</p>#', $html);
        $this->assertMatchesRegularExpression('#<h3>2\. Medium</h3>\s*<p class="empty">No &lt;alerts&gt;</p>#', $html);
        $this->assertStringContainsString('<h3>3. Contact form</h3>', $html);
        // An empty group has no table and no page
        $this->assertSame(1, substr_count($html, '<table>'));
        $this->assertStringNotContainsString('<div class="group">1. High</div>', $html);
    }

    public function testEmptyGroupInTheLanguageOfTheReport(): void
    {
        $doc = self::document();
        $doc->groups = [new Group(1, '高')];
        $doc->language = 'ja';
        $this->assertStringContainsString('<p class="empty">なし</p>', (new HtmlRenderer())->render($doc));

        // A language of another directory of messages only
        $doc->language = 'fr';
        $this->assertStringContainsString('<p class="empty">None</p>', (new HtmlRenderer())->render($doc));
    }

    public function testCheckLists(): void
    {
        $doc = self::document();
        $doc->checksTitle = 'Checks';
        $doc->checkLists = [
            new CheckList('Passed checks', ['Rule', 'Name', 'Risk', 'CWE'], [
                new Check('cakephp2-query-concatenation', 'SQL <built>', ['CWE-89'], 'high', 'High'),
                new Check('cakephp2-view-raw-output', 'Raw output', ['CWE-79']),
            ], note: 'The rules that ran & found nothing.'),
            new CheckList('Not run', ['Rule', 'Name', 'Reason'], [new Check('cakephp2-config-debug', 'Debug', ['The check is off', 'ignored'])]),
            new CheckList('Rulesets', ['Ruleset', 'Rules'], empty: 'No <rulesets>'),
        ];
        $html = (new HtmlRenderer())->render($doc);

        $this->assertStringContainsString('<section class="checks">', $html);
        $this->assertStringContainsString('<h2>Checks</h2>', $html);
        $this->assertStringContainsString('<h3>Passed checks</h3>', $html);
        $this->assertStringContainsString('<p class="check-note">The rules that ran &amp; found nothing.</p>', $html);
        $this->assertStringContainsString('<colgroup><col class="id" style="width: 48mm"><col><col class="res"><col></colgroup>', $html);
        $this->assertStringContainsString('<thead><tr><th>Rule</th><th>Name</th><th>Risk</th><th>CWE</th></tr></thead>', $html);
        $this->assertStringContainsString('<td class="mono">cakephp2-query-concatenation</td>', $html);
        $this->assertStringContainsString('<td>SQL &lt;built&gt;</td>', $html);
        $this->assertStringContainsString('<span class="badge high">High</span>', $html);
        $this->assertStringContainsString('<td>CWE-89</td>', $html);
        // Without a badge, the values follow the title; values without a heading are left out
        $this->assertStringContainsString('<colgroup><col class="id" style="width: 42mm"><col><col></colgroup>', $html);
        $this->assertStringContainsString('<td>The check is off</td>', $html);
        $this->assertStringNotContainsString('ignored', $html);
        $this->assertStringContainsString('<p class="empty">No &lt;rulesets&gt;</p>', $html);
        // After the list, before the pages of the items
        $this->assertGreaterThan(strpos($html, '<section class="list">'), strpos($html, '<section class="checks">'));
        $this->assertLessThan(strpos($html, '<section class="item"'), strpos($html, '<section class="checks">'));
    }

    public function testWidthOfTheIds(): void
    {
        $this->assertSame(20, (new CheckList('Rules', ['ID', 'Name'], [new Check('10202', 'Anti-CSRF')]))->idWidth());
        $this->assertSame(20, (new CheckList('Rules', ['ID', 'Name']))->idWidth());
        $this->assertSame(44, (new CheckList('Rules', ['ID', 'Name'], [new Check('cakephp2-order-request', 'Order')]))->idWidth());
        $this->assertSame(48, (new CheckList('Rules', ['ID', 'Name'], [new Check(str_repeat('x', 60), 'Long')]))->idWidth());
    }

    public function testNoCheckLists(): void
    {
        $this->assertStringNotContainsString('<section class="checks">', (new HtmlRenderer())->render(self::document()));
    }

    public function testCssString(): void
    {
        $this->assertSame('"a\5C b\22 \3C /style\3E "', HtmlRenderer::cssString('a\\b"</style>'));
    }
}
