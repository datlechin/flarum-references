<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References;

enum RelationType: string
{
    case References = 'references';
    case DuplicateOf = 'duplicate_of';
    case SeeAlso = 'see_also';
    case Supersedes = 'supersedes';
    case Answers = 'answers';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
