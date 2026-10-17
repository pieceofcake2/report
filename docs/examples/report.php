<?php
/**
 * Writes a sample report to the PDF file of the first argument: php docs/examples/report.php tmp/sample.pdf ja
 */
declare(strict_types=1);

use PieceOfCake2\Report\Count;
use PieceOfCake2\Report\Cover;
use PieceOfCake2\Report\Document;
use PieceOfCake2\Report\Environment;
use PieceOfCake2\Report\Group;
use PieceOfCake2\Report\Item;
use PieceOfCake2\Report\Message;
use PieceOfCake2\Report\ReportWriter;
use PieceOfCake2\Report\Row;
use PieceOfCake2\Report\Translator;
use PieceOfCake2\Report\Verdict;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$translator = new Translator($argv[2] ?? 'en');
$cover = new Cover($translator);
$environment = Environment::detect();
$startedAt = new DateTimeImmutable('-75 seconds');
$finishedAt = new DateTimeImmutable();
$title = 'Inspection Report';
$project = Cover::rootPackage();

$doc = new Document(
    title: $title,
    verdict: new Verdict(false, $translator->translate('verdict.failed'), [
        new Count(2, 'Items'),
        new Count(1, 'Passed', Count::PASS),
        new Count(1, 'Failed', Count::FAIL),
    ]),
    columns: [$translator->translate('field.no'), 'Item', $translator->translate('field.result'), 'Time'],
    groups: [
        new Group(1, 'Contact form', [
            new Item('1-1', 'Sends the message', 'pass', 'Passed', '1.2s', [new Row('Name', 'ContactsTest::testSend', monoValue: true)]),
            new Item('1-2', 'Shows the errors', 'fail', 'Failed', '0.4s', description: 'The required fields are checked.', message: new Message('Failure', 'The error message was not shown.')),
        ]),
    ],
    project: $project,
    target: $cover->target($environment),
    run: $cover->run($startedAt, $finishedAt, 75, $environment),
    listTitle: 'Items',
    targetTitle: $translator->translate('section.target'),
    runTitle: $translator->translate('section.run'),
    headerLeft: Cover::headerLeft($title, $project),
    headerRight: $cover->headerRight($startedAt, $environment),
    language: $translator->language(),
);

(new ReportWriter())->write($doc, $argv[1] ?? 'tmp/sample.pdf');
