<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Extraction;

use Datlechin\References\ReferenceOrigin;

final class ExtractedReference
{
    private function __construct(
        public readonly string $targetType,
        public readonly int $targetId,
        public readonly ReferenceOrigin $origin,
    ) {
    }

    public static function make(string $targetType, int $targetId, ReferenceOrigin $origin): self
    {
        return new self($targetType, $targetId, $origin);
    }

    public function key(): string
    {
        return $this->targetType.':'.$this->targetId;
    }
}
