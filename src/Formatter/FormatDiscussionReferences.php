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
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Flarum\Locale\TranslatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use s9e\TextFormatter\Renderer;
use s9e\TextFormatter\Utils;

/**
 * The title is read again at render, so a discussion renamed years after the
 * post still reads correctly.
 */
final class FormatDiscussionReferences
{
    public function __construct(
        private TranslatorInterface $translator,
        private SlugManager $slugs,
    ) {
    }

    public function __invoke(Renderer $renderer, mixed $context, string $xml, ?ServerRequestInterface $request = null): string
    {
        // Scoped to whoever is reading, so a discussion they cannot open falls
        // into the branch below and reads as deleted rather than handing over
        // its title. No request means no reader to scope to, which is mail and
        // console rendering; scoping those to a guest would render every
        // reference in a notification email as deleted.
        $discussions = DiscussionReferenceLoader::load($xml, $request ? RequestUtil::getActor($request) : null);

        if ($discussions === null) {
            return $xml;
        }

        return Utils::replaceAttributes($xml, ConfigureDiscussionReferences::TAG_NAME, function (array $attributes) use ($discussions) {
            $discussion = $discussions[(int) ($attributes['id'] ?? 0)] ?? null;

            if ($discussion instanceof Discussion) {
                $attributes['title'] = $discussion->title;
                $attributes['slug'] = $this->slugs->forResource(Discussion::class)->toSlug($discussion);
                $attributes['deleted'] = false;
            } else {
                $attributes['title'] = $this->translator->trans('datlechin-references.forum.reference.deleted_text');
                $attributes['deleted'] = true;
            }

            return $attributes;
        });
    }
}
