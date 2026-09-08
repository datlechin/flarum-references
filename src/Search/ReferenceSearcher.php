<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Search;

use Datlechin\References\Reference;
use Flarum\Search\Database\AbstractSearcher;
use Flarum\User\User;
use Illuminate\Database\Eloquent\Builder;

final class ReferenceSearcher extends AbstractSearcher
{
    public function getQuery(User $actor): Builder
    {
        return Reference::whereVisibleTo($actor);
    }
}
