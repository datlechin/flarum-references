<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Api\Controller;

use Flarum\Group\Group;
use Flarum\User\User;

/**
 * Cached widgets are keyed by the actor's groups, not by the actor. Visibility
 * follows from group membership, and a key per member would leave the cache
 * empty on any forum busy enough to need one.
 */
abstract class CacheKey
{
    public static function forActor(string $prefix, User $actor): string
    {
        $groups = [];

        foreach ($actor->groups as $group) {
            if ($group instanceof Group) {
                $groups[] = (string) $group->id;
            }
        }

        sort($groups);

        return $prefix.'.'.md5(implode(',', $groups));
    }

    public static function id(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
