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
use Flarum\User\User;
use s9e\TextFormatter\Utils;

/**
 * One query per post rather than one per reference. A post citing six
 * discussions used to cost six lookups on every render and six more on every
 * edit.
 */
abstract class DiscussionReferenceLoader
{
    /**
     * The title is read from the database and shown to whoever is reading the
     * post, so an unscoped lookup hands out the titles of discussions the
     * reader cannot open, and a post full of made up ids reads them back in
     * bulk. A null actor means there is nobody to scope to, which is the email
     * and console case rather than a guest.
     *
     * @return array<int, Discussion>|null null when the post has no references
     */
    public static function load(string $xml, ?User $actor = null): ?array
    {
        if (! str_contains($xml, '<'.ConfigureDiscussionReferences::TAG_NAME)) {
            return null;
        }

        $ids = [];

        foreach (Utils::getAttributeValues($xml, ConfigureDiscussionReferences::TAG_NAME, 'id') as $id) {
            if (ctype_digit($id)) {
                $ids[] = (int) $id;
            }
        }

        if ($ids === []) {
            return null;
        }

        $discussions = [];

        $query = Discussion::query()->whereIn('id', array_unique($ids));

        if ($actor !== null) {
            $query->whereVisibleTo($actor);
        }

        foreach ($query->get() as $discussion) {
            $discussions[(int) $discussion->id] = $discussion;
        }

        return $discussions;
    }
}
