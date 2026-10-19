<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

use InvalidArgumentException;

/**
 * Renders a report as an HTML page for Chrome to print (`resources/report.php` and `report.css`).
 *
 * The page is A4 with a header and the page numbers (CSS page margin boxes): the cover with the result, the
 * list of the items by group, the tables of checks, then a page for each item.
 */
final class HtmlRenderer
{
    public const RESOURCE_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources';

    /**
     * Renders the report
     *
     * @param \PieceOfCake2\Report\Document $doc Report
     * @return string HTML
     */
    public function render(Document $doc): string
    {
        $style = (string)file_get_contents(self::RESOURCE_DIR . DIRECTORY_SEPARATOR . 'report.css');
        if ($doc->css !== '') {
            $style .= $doc->css;
        }

        return self::include(self::RESOURCE_DIR . DIRECTORY_SEPARATOR . 'report.php', [
            'doc' => $doc,
            'style' => $style,
            'none' => self::none($doc->language),
            'h' => static fn(?string $text): string => htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'css' => static fn(string $text): string => self::cssString($text),
        ]);
    }

    /**
     * The text of an empty group or table of checks in a language: `None` in a language that this package does not
     * have
     *
     * @param string $language Language
     * @return string
     */
    private static function none(string $language): string
    {
        try {
            return (new Translator($language))->translate('group.empty');
        } catch (InvalidArgumentException) {
            return (new Translator())->translate('group.empty');
        }
    }

    /**
     * A CSS string, e.g. for the `content` of a page margin box
     *
     * @param string $text Text
     * @return string
     */
    public static function cssString(string $text): string
    {
        return '"' . (string)preg_replace_callback('/[\\\\"\x00-\x1f\x7f<>]/', static fn(array $m): string => sprintf('\\%X ', ord($m[0])), $text) . '"';
    }

    /**
     * Renders a PHP template
     *
     * @param string $template Path of the template
     * @param array<string, mixed> $variables Variables of the template
     * @return string
     */
    private static function include(string $template, array $variables): string
    {
        extract($variables);
        ob_start();
        try {
            include $template;
        } finally {
            $html = (string)ob_get_clean();
        }

        return $html;
    }
}
