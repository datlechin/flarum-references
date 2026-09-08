<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Target;

use Datlechin\References\Contract\ReferenceTarget;
use Illuminate\Database\Eloquent\Relations\Relation;

final class TargetRegistry
{
    /**
     * @var array<string, ReferenceTarget>
     */
    private array $targets = [];

    public function add(ReferenceTarget $target): void
    {
        $this->targets[$target->key()] = $target;

        Relation::morphMap([$target->key() => $target->modelClass()], merge: true);
    }

    public function get(string $key): ?ReferenceTarget
    {
        return $this->targets[$key] ?? null;
    }

    /**
     * @return array<string, ReferenceTarget>
     */
    public function all(): array
    {
        return $this->targets;
    }

    /**
     * @return list<string>
     */
    public function keys(): array
    {
        return array_keys($this->targets);
    }
}
