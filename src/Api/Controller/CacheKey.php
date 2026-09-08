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

/**
 * There used to be a `forActor()` here that keyed a cached payload by the
 * actor's groups. Visibility does not follow from group membership: core grants
 * a discussion to its own author whether or not anybody shares a group with
 * them, so two readers hashing alike could be served each other's titles. What
 * is cached now is only the part that is the same for everyone.
 */
abstract class CacheKey
{
    public static function id(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
