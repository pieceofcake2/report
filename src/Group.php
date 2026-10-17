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
     */
    public function __construct(
        public int $no,
        public string $title,
        public array $items = [],
    ) {
    }
}
