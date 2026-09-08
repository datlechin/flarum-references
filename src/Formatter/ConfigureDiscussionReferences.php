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
use Flarum\Http\UrlGenerator;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
use s9e\TextFormatter\Configurator\Items\Tag as ConfiguratorTag;
use s9e\TextFormatter\Parser\Tag as FormatterTag;

/**
 * `@"Discussion title"#d123`.
 *
 * Every mention regex in flarum/mentions guards its display name with
 * `(?!"#[a-z]{0,3}[0-9]+)`, which reserves `"#<letters><digits>` for exactly
 * this. User is `"#123`, post `"#p123`, group `"#g123`, so `"#d123` is a free
 * slot rather than a squat. Tag mentions cannot reach it either: their regex
 * refuses a `#` that follows a quote.
 *
 * That disjointness is what makes this safe. Two matchers claiming one span
 * would have to be settled by s9e's tag priority, and the Preg plugin gives
 * every tag it builds the same priority, leaving extension load order to
 * decide.
 */
final class ConfigureDiscussionReferences
{
    public const TAG_NAME = 'DISCUSSIONREFERENCE';

    public const REGEX = '/\B@["“](?<title>((?!"#[a-z]{0,3}[0-9]+).)+)["”]#d(?<id>[0-9]+)\b/';

    public function __construct(
        private UrlGenerator $url,
    ) {
    }

    public function __invoke(Configurator $config): void
    {
        $config->rendering->parameters['DISCUSSION_REFERENCE_URL'] = $this->url->to('forum')->route('discussion', ['id' => '']);

        /** @var ConfiguratorTag $tag */
        $tag = $config->tags->add(self::TAG_NAME);

        /** @var Attribute $id */
        $id = $tag->attributes->add('id');
        $id->filterChain->append('#uint');

        $tag->attributes->add('title');

        // The address wants the slug and the browser wants the number, so they
        // are two attributes rather than one that has to be both.
        /** @var Attribute $slug */
        $slug = $tag->attributes->add('slug');
        $slug->required = false;

        /** @var Attribute $deleted */
        $deleted = $tag->attributes->add('deleted');
        $deleted->required = false;

        $tag->template = '
            <xsl:choose>
                <xsl:when test="@deleted != 1">
                    <a href="{$DISCUSSION_REFERENCE_URL}{@slug}" class="DiscussionReference" data-id="{@id}"><i class="icon fas fa-comments DiscussionReference-icon"></i><xsl:value-of select="@title"/></a>
                </xsl:when>
                <xsl:otherwise>
                    <span class="DiscussionReference DiscussionReference--deleted" data-id="{@id}"><i class="icon fas fa-comments DiscussionReference-icon"></i><xsl:value-of select="@title"/></span>
                </xsl:otherwise>
            </xsl:choose>';

        /** @var ProgrammableCallback $filter */
        $filter = $tag->filterChain->prepend([static::class, 'addDiscussionTitle']);
        $filter->setJS('function(tag) { return flarum.extensions["datlechin-references"].filterDiscussionReferences(tag); }');

        $config->Preg->match(self::REGEX, self::TAG_NAME);
    }

    public static function addDiscussionTitle(FormatterTag $tag): ?bool
    {
        $discussion = Discussion::query()->find($tag->getAttribute('id'));

        if ($discussion instanceof Discussion) {
            $tag->setAttribute('title', $discussion->title);
            $tag->setAttribute('slug', (string) $discussion->id);

            return true;
        }

        $tag->invalidate();

        return null;
    }
}
