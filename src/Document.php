<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

/**
 * The content of a report: the cover, the list of the items by group, then a page for each item.
 */
final class Document
{
    /**
     * Constructor
     *
     * @param string $title Title, also in the header of the pages
     * @param \PieceOfCake2\Report\Verdict $verdict Result on the cover
     * @param array<string> $columns Headings of the list: number, title, result and, when the items have one, the
     *  extra column
     * @param array<\PieceOfCake2\Report\Group> $groups Items by group
     * @param string|null $project Name of the project under the title
     * @param array<\PieceOfCake2\Report\Row> $target Table of what was checked, e.g. the repository and commit
     * @param array<\PieceOfCake2\Report\Row> $run Table of the run, e.g. the date and environment
     * @param string|null $note Note at the end of the cover
     * @param string $listTitle Heading of the list
     * @param string $targetTitle Heading of the target table
     * @param string $runTitle Heading of the run table
     * @param string $headerLeft Header of the pages after the cover, on the left
     * @param string $headerRight Header of the pages after the cover, on the right
     * @param string $css CSS added after the CSS of the report, e.g. for the bodies of the items
     * @param string $language Language of the page, e.g. `en` or `ja`
     */
    public function __construct(
        public string $title,
        public Verdict $verdict,
        public array $columns,
        public array $groups = [],
        public ?string $project = null,
        public array $target = [],
        public array $run = [],
        public ?string $note = null,
        public string $listTitle = '',
        public string $targetTitle = '',
        public string $runTitle = '',
        public string $headerLeft = '',
        public string $headerRight = '',
        public string $css = '',
        public string $language = Translator::DEFAULT_LANGUAGE,
    ) {
    }
}
