<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * A boxed message of an item page, e.g. the message of a failure.
 */
final class Message
{
    public const FAIL = 'fail';
    public const NOTICE = 'notice';

    /**
     * Constructor
     *
     * @param string $label Heading of the box
     * @param string $text Text, shown preformatted
     * @param string $class `Message::FAIL` (red) or `Message::NOTICE` (yellow)
     */
    public function __construct(
        public string $label,
        public string $text,
        public string $class = self::FAIL,
    ) {
    }
}
