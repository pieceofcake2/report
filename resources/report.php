<?php
/**
 * The HTML of a report, printed to PDF by Chrome (see `PieceOfCake2\Report\HtmlRenderer`)
 *
 * @var \PieceOfCake2\Report\Document $doc
 * @var string $style CSS of the report and of the document
 * @var \Closure(?string): string $h Escapes HTML
 * @var \Closure(string): string $css CSS string
 */
$extra = $doc->columns[3] ?? null;
?><!doctype html>
<html lang="<?= $h($doc->language) ?>">
<head>
<meta charset="utf-8">
<title><?= $h($doc->title) ?></title>
<style>
<?= $style ?>
@page { @top-left { content: <?= $css($doc->headerLeft) ?>; } @top-right { content: <?= $css($doc->headerRight) ?>; } }
@page :first { @top-left { content: none; } @top-right { content: none; } }
</style>
</head>
<body>

<section class="cover">
<h1><?= $h($doc->title) ?></h1>
<?php if ($doc->project !== null) : ?>
    <div class="project"><?= $h($doc->project) ?></div>
<?php endif; ?>

<div class="verdict <?= $doc->verdict->passed ? 'passed' : 'failed' ?>">
<div class="label"><?= $h($doc->verdict->label) ?></div>
<div class="counts">
<?php foreach ($doc->verdict->counts as $count) : ?>
    <div><b class="<?= $h($count->class) ?>"><?= $count->value ?></b><span><?= $h($count->label) ?></span></div>
<?php endforeach; ?>
</div>
</div>

<?php foreach ([[$doc->targetTitle, $doc->target], [$doc->runTitle, $doc->run]] as [$heading, $rows]) : ?>
    <?php if ($rows !== []) : ?>
        <h2><?= $h($heading) ?></h2>
        <table class="kv">
        <?php foreach ($rows as $row) : ?>
            <tr>
            <th><?= $h($row->label) ?></th>
            <td>
            <?php if ($row->mono !== null) : ?>
                <span class="mono"><?= $h($row->mono) ?></span>
            <?php endif; ?>
            <?= $h($row->value) ?>
            <?php if ($row->sub !== null) : ?>
                <br><span class="mono sub"><?= $h($row->sub) ?></span>
            <?php endif; ?>
            <?php if ($row->warning !== null) : ?>
                <br><span class="warn"><?= $h($row->warning) ?></span>
            <?php endif; ?>
            </td>
            </tr>
        <?php endforeach; ?>
        </table>
    <?php endif; ?>
<?php endforeach; ?>

<?php if ($doc->note !== null) : ?>
    <p class="note"><?= $h($doc->note) ?></p>
<?php endif; ?>
</section>

<section class="list">
<h2><?= $h($doc->listTitle) ?></h2>
<?php foreach ($doc->groups as $group) : ?>
    <div class="group-list">
    <h3><?= $group->no ?>. <?= $h($group->title) ?></h3>
    <table>
    <colgroup><col class="no"><col><col class="res"><?= $extra !== null ? '<col class="time">' : '' ?></colgroup>
    <thead><tr><th><?= $h($doc->columns[0] ?? '') ?></th><th><?= $h($doc->columns[1] ?? '') ?></th><th><?= $h($doc->columns[2] ?? '') ?></th><?= $extra !== null ? '<th>' . $h($extra) . '</th>' : '' ?></tr></thead>
    <tbody>
    <?php foreach ($group->items as $item) : ?>
        <tr>
        <td><?= $h($item->no) ?></td>
        <td><a href="#<?= $h($item->anchor()) ?>"><?= $h($item->title) ?></a></td>
        <td class="res"><span class="badge <?= $h($item->badge) ?>"><?= $h($item->badgeLabel) ?></span></td>
        <?php if ($extra !== null) : ?>
            <td class="num"><?= $h($item->extra) ?></td>
        <?php endif; ?>
        </tr>
    <?php endforeach; ?>
    </tbody>
    </table>
    </div>
<?php endforeach; ?>
</section>

<?php foreach ($doc->groups as $group) : ?>
    <?php foreach ($group->items as $item) : ?>
        <section class="item" id="<?= $h($item->anchor()) ?>">
        <div class="group"><?= $group->no ?>. <?= $h($group->title) ?></div>
        <div class="head"><h3><?= $h($item->no) ?>　<?= $h($item->title) ?></h3><span class="badge <?= $h($item->badge) ?>"><?= $h($item->badgeLabel) ?></span></div>
        <?php if ($item->meta !== []) : ?>
            <table class="meta">
            <?php foreach ($item->meta as $row) : ?>
                <tr><th><?= $h($row->label) ?></th><td<?= $row->monoValue ? ' class="mono"' : '' ?>><?= $h($row->value) ?></td></tr>
            <?php endforeach; ?>
            </table>
        <?php endif; ?>
        <?php if ($item->description !== null) : ?>
            <p class="desc"><?= nl2br($h($item->description)) ?></p>
        <?php endif; ?>
        <?php if ($item->message !== null) : ?>
            <div class="message <?= $h($item->message->class) ?>">
            <b><?= $h($item->message->label) ?></b>
            <pre><?= $h($item->message->text) ?></pre>
            </div>
        <?php endif; ?>
        <?= $item->body ?>
        </section>
    <?php endforeach; ?>
<?php endforeach; ?>

</body>
</html>
