<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * A number of the verdict on the cover, e.g. the number of failed tests.
 */
final class Count
{
    public const PASS = 't-pass';
    public const FAIL = 't-fail';

    /**
     * Constructor
     *
     * @param int $value Number
     * @param string $label Label under the number
     * @param string $class CSS class of the number: `Count::PASS`, `Count::FAIL` or one of the CSS of the report
     */
    public function __construct(
        public int $value,
        public string $label,
        public string $class = '',
    ) {
    }
}
