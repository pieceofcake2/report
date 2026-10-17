<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * A row of a table of the cover (target, run) or of the table at the top of an item page.
 */
final class Row
{
    /**
     * Constructor
     *
     * @param string $label Label (the `th`)
     * @param string $value Value
     * @param string|null $mono A value in a monospaced font before the value, e.g. a commit hash
     * @param string|null $sub A second line in a small monospaced font, e.g. a URL
     * @param string|null $warning A second line as a warning
     * @param bool $monoValue Whether the value itself is in a monospaced font
     */
    public function __construct(
        public string $label,
        public string $value,
        public ?string $mono = null,
        public ?string $sub = null,
        public ?string $warning = null,
        public bool $monoValue = false,
    ) {
    }
}
