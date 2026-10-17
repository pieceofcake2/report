<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * The result on the cover: passed or failed, with its numbers.
 */
final class Verdict
{
    /**
     * Constructor
     *
     * @param bool $passed Whether it passed (green) or failed (red)
     * @param string $label Label, e.g. `Passed`
     * @param array<\PieceOfCake2\Report\Count> $counts Numbers beside the label
     */
    public function __construct(
        public bool $passed,
        public string $label,
        public array $counts = [],
    ) {
    }
}
