<?php
declare(strict_types=1);

namespace PieceOfCake2\Report;

use InvalidArgumentException;
use JsonException;

/**
 * The headings and labels of a report in a language.
 *
 * The messages are JSON files named after the language (`en.json`, `ja.json`): the common ones of this package in
 * `resources/locales/`, then those of the directories given, in order, which add messages (e.g. the labels of
 * a kind of report), languages, or replace messages. A message that the language does not have is the English
 * one. Placeholders are written as `{name}`, so that a language can put the values in its own order.
 */
final class Translator
{
    public const DEFAULT_LANGUAGE = 'en';

    /**
     * The directory of the common messages
     */
    public const LOCALE_DIR = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'locales';

    /**
     * @var array<string, string>
     */
    private array $messages = [];

    /**
     * Constructor
     *
     * @param string $language Language, e.g. `en` or `ja`
     * @param array<string|null> $localeDirs Directories of more `<language>.json` files, later ones win
     * @throws \InvalidArgumentException When no directory has the language
     */
    public function __construct(private string $language = self::DEFAULT_LANGUAGE, array $localeDirs = [])
    {
        if (!preg_match('/\A[A-Za-z0-9_-]+\z/', $language)) {
            throw new InvalidArgumentException(sprintf('Invalid report language "%s".', $language));
        }

        $dirs = [self::LOCALE_DIR];
        foreach ($localeDirs as $dir) {
            if ($dir !== null && $dir !== '') {
                $dirs[] = rtrim($dir, '/\\');
            }
        }

        $found = $language === self::DEFAULT_LANGUAGE;
        foreach (array_unique([self::DEFAULT_LANGUAGE, $language]) as $lang) {
            foreach ($dirs as $dir) {
                $file = $dir . DIRECTORY_SEPARATOR . $lang . '.json';
                if (!is_file($file)) {
                    continue;
                }
                $found = $found || $lang === $language;
                $this->messages = array_merge($this->messages, self::load($file));
            }
        }

        if (!$found) {
            throw new InvalidArgumentException(sprintf(
                'No messages for the report language "%s": add %s.json to a locale directory.',
                $language,
                $language,
            ));
        }
    }

    /**
     * The language, e.g. for the `lang` attribute of the HTML
     *
     * @return string
     */
    public function language(): string
    {
        return $this->language;
    }

    /**
     * The message of a key with its placeholders replaced
     *
     * @param string $key Key, e.g. `field.commit`
     * @param array<string, string|int|float> $params Values of the `{name}` placeholders
     * @return string The key itself when no language has it
     */
    public function translate(string $key, array $params = []): string
    {
        $message = $this->messages[$key] ?? $key;
        $replace = [];
        foreach ($params as $name => $value) {
            $replace['{' . $name . '}'] = (string)$value;
        }

        return strtr($message, $replace);
    }

    /**
     * The messages of a JSON file
     *
     * @param string $file Path of the file
     * @return array<string, string>
     * @throws \InvalidArgumentException When the file is not a JSON object of strings
     */
    private static function load(string $file): array
    {
        try {
            $messages = json_decode((string)file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new InvalidArgumentException(sprintf('Invalid JSON in %s: %s', $file, $e->getMessage()), 0, $e);
        }
        if (!is_array($messages)) {
            throw new InvalidArgumentException(sprintf('%s must be a JSON object of messages.', $file));
        }

        $result = [];
        foreach ($messages as $key => $message) {
            if (is_string($message)) {
                $result[(string)$key] = $message;
            }
        }

        return $result;
    }
}
