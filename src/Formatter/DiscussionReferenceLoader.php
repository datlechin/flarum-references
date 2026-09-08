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
use s9e\TextFormatter\Utils;

/**
 * One query per post rather than one per reference. A post citing six
 * discussions used to cost six lookups on every render and six more on every
 * edit.
 */
abstract class DiscussionReferenceLoader
{
    /**
     * @return array<int, Discussion>|null null when the post has no references
     */
    public static function load(string $xml): ?array
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

        foreach (Discussion::query()->whereIn('id', array_unique($ids))->get() as $discussion) {
            $discussions[(int) $discussion->id] = $discussion;
        }

        return $discussions;
    }
}
