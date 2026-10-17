<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * An item of the report: a row of the list and a page of its own, e.g. a test or a finding.
 */
final class Item
{
    /**
     * Constructor
     *
     * @param string $no Number shown in the list and on the page, e.g. `1-2`
     * @param string $title Title
     * @param string $badge CSS class of the badge, e.g. `pass`, `fail` or `skip`
     * @param string $badgeLabel Text of the badge
     * @param string|null $extra Value of the last column of the list, e.g. the time
     * @param array<\PieceOfCake2\Report\Row> $meta Table at the top of the page
     * @param string|null $description Paragraph under the table
     * @param \PieceOfCake2\Report\Message|null $message Boxed message
     * @param string $body HTML at the end of the page, e.g. screenshots or evidence (not escaped)
     * @param string|null $id Anchor of the page, default: `i` and the number
     */
    public function __construct(
        public string $no,
        public string $title,
        public string $badge,
        public string $badgeLabel,
        public ?string $extra = null,
        public array $meta = [],
        public ?string $description = null,
        public ?Message $message = null,
        public string $body = '',
        public ?string $id = null,
    ) {
    }

    /**
     * The anchor of the page
     *
     * @return string
     */
    public function anchor(): string
    {
        return $this->id ?? 'i' . $this->no;
    }
}
