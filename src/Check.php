<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * A row of a `CheckList`: a check that ran without finding anything, or that did not run, e.g. a rule of a scan.
 * It has no page of its own.
 */
final class Check
{
    /**
     * Constructor
     *
     * @param string $id ID, in a monospaced font, e.g. `cakephp2-query-concatenation` or `10202`
     * @param string $title Title
     * @param array<string> $values Values of the columns after the title (and after the badge), e.g. the CWE or
     *  why it did not run
     * @param string|null $badge CSS class of the badge, e.g. `high`, or null for no badge
     * @param string $badgeLabel Text of the badge
     */
    public function __construct(
        public string $id,
        public string $title,
        public array $values = [],
        public ?string $badge = null,
        public string $badgeLabel = '',
    ) {
    }
}
