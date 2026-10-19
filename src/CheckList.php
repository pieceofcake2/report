<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * A compact table of checks, after the list of the items: e.g. the rules that ran and found nothing, or those that
 * did not run and why. A check has no page of its own, so that hundreds fit on a few pages.
 */
final class CheckList
{
    /**
     * Constructor
     *
     * @param string $title Heading
     * @param array<string> $columns Headings of the table: the ID, the title, the badge when the checks have one,
     *  then a heading for each of their values
     * @param array<\PieceOfCake2\Report\Check> $checks Checks
     * @param string|null $note Paragraph under the heading, e.g. what the checks are
     * @param string|null $empty Text shown when there are no checks, default: `None` in the language of the report
     */
    public function __construct(
        public string $title,
        public array $columns,
        public array $checks = [],
        public ?string $note = null,
        public ?string $empty = null,
    ) {
    }

    /**
     * The width of the ID column in millimeters: for the longest ID in the monospaced font, from 20 to 48 mm, the
     * longer IDs wrapping
     *
     * @return int
     */
    public function idWidth(): int
    {
        $length = max(array_merge([0], array_map(static fn(Check $check): int => mb_strlen($check->id), $this->checks)));

        return (int)min(48, max(20, ceil($length * 1.75 + 5)));
    }

    /**
     * Whether the checks have a badge column
     *
     * @return bool
     */
    public function hasBadge(): bool
    {
        foreach ($this->checks as $check) {
            if ($check->badge !== null) {
                return true;
            }
        }

        return false;
    }
}
