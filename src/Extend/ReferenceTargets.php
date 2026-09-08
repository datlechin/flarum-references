<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Extend;

use Datlechin\References\Contract\ReferenceTarget;
use Datlechin\References\Target\TargetRegistry;
use Flarum\Extend\ExtenderInterface;
use Flarum\Extension\Extension;
use Illuminate\Contracts\Container\Container;

/**
 * Registers a new kind of thing a reference can point at.
 *
 * ```php
 * (new ReferenceTargets())->add(WikiPageTarget::class),
 * ```
 *
 * One call reaches URL extraction, the manual reference picker, the
 * polymorphic `target` relationship, the morph map and the moderation UI. Wrap
 * it in `Extend\Conditional()->whenExtensionEnabled(...)` when the model
 * belongs to another extension.
 */
final class ReferenceTargets implements ExtenderInterface
{
    /**
     * @var list<class-string<ReferenceTarget>>
     */
    private array $targets = [];

    /**
     * @param class-string<ReferenceTarget> $targetClass
     */
    public function add(string $targetClass): self
    {
        $this->targets[] = $targetClass;

        return $this;
    }

    public function extend(Container $container, ?Extension $extension = null): void
    {
        $container->extend(
            TargetRegistry::class,
            function (TargetRegistry $registry, Container $container) {
                foreach ($this->targets as $targetClass) {
                    $registry->add($container->make($targetClass));
                }

                return $registry;
            },
        );
    }
}
