<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Formatter;

use Flarum\Discussion\Discussion;
use Flarum\Locale\TranslatorInterface;
use s9e\TextFormatter\Utils;

final class UnparseDiscussionReferences
{
    public function __construct(
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(mixed $context, ?string $xml): ?string
    {
        if ($xml === null) {
            return null;
        }

        $discussions = DiscussionReferenceLoader::load($xml);

        if ($discussions === null) {
            return $xml;
        }

        return $this->unparse($this->refreshTitles($xml, $discussions));
    }

    /**
     * @param array<int, Discussion> $discussions
     */
    protected function refreshTitles(string $xml, array $discussions): string
    {
        return Utils::replaceAttributes($xml, ConfigureDiscussionReferences::TAG_NAME, function (array $attributes) use ($discussions) {
            $discussion = $discussions[(int) ($attributes['id'] ?? 0)] ?? null;

            $attributes['title'] = self::sanitiseTitle(
                $discussion instanceof Discussion
                    ? $discussion->title
                    : $this->translator->trans('datlechin-references.forum.reference.deleted_text')
            );

            return $attributes;
        });
    }

    /**
     * A title carrying the shape this syntax ends with would cut the regex
     * short on the next parse. Discussion titles run into that far more often
     * than usernames do, which is why mentions has the same guard.
     */
    public static function sanitiseTitle(string $title): string
    {
        return str_contains($title, '"#')
            ? (preg_replace('/"#[a-z]{0,3}[0-9]+/', '_', $title) ?? $title)
            : $title;
    }

    /**
     * Both lookaheads scan from just after the tag name, so this does not care
     * which order s9e serialised the attributes in. Mentions' own version
     * reads them in sequence and only works because its two attributes happen
     * to sort that way.
     */
    protected function unparse(string $xml): string
    {
        $tag = preg_quote(ConfigureDiscussionReferences::TAG_NAME, '/');

        return preg_replace(
            '/<'.$tag.'\b(?=[^>]*\bid="([0-9]+)")(?=[^>]*\btitle="([^"]*)")[^>]*>[^<]*<\/'.$tag.'>/',
            '@"$2"#d$1',
            $xml,
        ) ?? $xml;
    }
}
