<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

use Composer\InstalledVersions;
use DateTimeImmutable;

/**
 * The common rows and texts of the cover and the header: what was checked (repository, branch, commit), when and
 * where it ran, and how long it took.
 */
final class Cover
{
    /**
     * Constructor
     *
     * @param \PieceOfCake2\Report\Translator $translator Messages
     */
    public function __construct(private Translator $translator)
    {
    }

    /**
     * The rows of the target: repository, branch and commit
     *
     * @param \PieceOfCake2\Report\Environment $environment Environment
     * @return array<\PieceOfCake2\Report\Row>
     */
    public function target(Environment $environment): array
    {
        $rows = [];
        if ($environment->repository !== null) {
            $rows[] = new Row($this->t('field.repository'), $environment->repository);
        }
        if ($environment->branch !== null) {
            $rows[] = new Row($this->t('field.branch'), $environment->branch);
        }
        if ($environment->commit !== null) {
            $rows[] = new Row(
                $this->t('field.commit'),
                (string)$environment->commitSubject,
                mono: $environment->commit,
                warning: $environment->dirty ? $this->t('commit.dirty') : null,
            );
        }

        return $rows;
    }

    /**
     * The rows of the run: date, duration and environment
     *
     * @param \DateTimeImmutable $startedAt Start
     * @param \DateTimeImmutable $finishedAt End
     * @param float $seconds Duration in seconds
     * @param \PieceOfCake2\Report\Environment $environment Environment
     * @return array<\PieceOfCake2\Report\Row>
     */
    public function run(DateTimeImmutable $startedAt, DateTimeImmutable $finishedAt, float $seconds, Environment $environment): array
    {
        $end = $startedAt->format('Y-m-d') === $finishedAt->format('Y-m-d') ? $finishedAt->format('H:i:s') : $finishedAt->format('Y-m-d H:i:s');
        $rows = [
            new Row($this->t('field.date'), $this->t('date.range', [
                'start' => $startedAt->format('Y-m-d H:i:s'),
                'end' => $end,
                'timezone' => $startedAt->getTimezone()->getName(),
            ])),
            new Row($this->t('field.duration'), $this->duration($seconds)),
        ];
        if ($environment->run !== null) {
            $rows[] = new Row($this->t('field.environment'), $environment->run, sub: $environment->runUrl);
        } elseif ($environment->os !== null) {
            $rows[] = new Row($this->t('field.environment'), $this->t('environment.local', ['os' => $environment->os]));
        }

        return $rows;
    }

    /**
     * The right header of the pages: the date of the run and the commit
     *
     * @param \DateTimeImmutable $startedAt Start
     * @param \PieceOfCake2\Report\Environment $environment Environment
     * @return string
     */
    public function headerRight(DateTimeImmutable $startedAt, Environment $environment): string
    {
        $run = $this->t('header.run', ['date' => $startedAt->format('Y-m-d H:i')]);

        return $environment->commit === null ? $run : $run . '　' . $environment->commit;
    }

    /**
     * A duration, e.g. `2m 14s`
     *
     * @param float $seconds Seconds
     * @return string
     */
    public function duration(float $seconds): string
    {
        $total = (int)round($seconds);
        $hours = intdiv($total, 3600);
        $minutes = intdiv($total % 3600, 60);
        $rest = $total % 60;
        if ($hours > 0) {
            return $this->t('duration.hours', ['hours' => $hours, 'minutes' => sprintf('%02d', $minutes), 'seconds' => sprintf('%02d', $rest)]);
        }
        if ($minutes > 0) {
            return $this->t('duration.minutes', ['minutes' => $minutes, 'seconds' => $rest]);
        }

        return $this->t('duration.seconds', ['seconds' => $rest]);
    }

    /**
     * The left header of the pages: the title and the project
     *
     * @param string $title Title
     * @param string|null $project Project
     * @return string
     */
    public static function headerLeft(string $title, ?string $project): string
    {
        return $project === null ? $title : $title . '　' . $project;
    }

    /**
     * The name of the Composer root package, e.g. `vendor/app`
     *
     * @return string|null
     */
    public static function rootPackage(): ?string
    {
        if (!class_exists(InstalledVersions::class)) {
            return null;
        }
        $name = InstalledVersions::getRootPackage()['name'];

        return $name === '' || $name === '__root__' ? null : $name;
    }

    /**
     * A Composer package and its version, e.g. `pieceofcake2/report 1.0.0`, or null when it is not installed
     *
     * @param string $name Package
     * @return string|null
     */
    public static function package(string $name): ?string
    {
        if (!class_exists(InstalledVersions::class) || !InstalledVersions::isInstalled($name)) {
            return null;
        }

        return $name . ' ' . InstalledVersions::getPrettyVersion($name);
    }

    /**
     * A message
     *
     * @param string $key Key
     * @param array<string, string|int|float> $params Placeholders
     * @return string
     */
    private function t(string $key, array $params = []): string
    {
        return $this->translator->translate($key, $params);
    }
}
