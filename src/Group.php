<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * A group of items, e.g. the tests of a class or the findings of a category.
 */
final class Group
{
    /**
     * Constructor
     *
     * @param int $no Number of the group
     * @param string $title Title
     * @param array<\PieceOfCake2\Report\Item> $items Items
     * @param string|null $empty Text of the list when the group has no items, default: `None` in the language of the
     *  report, so that a reader tells an empty group from one that was left out
     */
    public function __construct(
        public int $no,
        public string $title,
        public array $items = [],
        public ?string $empty = null,
    ) {
    }
}
