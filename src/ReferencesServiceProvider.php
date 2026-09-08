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

use Datlechin\References\Extraction\MentionReferenceExtractor;
use Datlechin\References\Extraction\ShortRefExtractor;
use Datlechin\References\Extraction\UrlReferenceExtractor;
use Datlechin\References\Service\ReferenceSyncer;
use Datlechin\References\Target\DiscussionTarget;
use Datlechin\References\Target\PostTarget;
use Datlechin\References\Target\TargetRegistry;
use Flarum\Foundation\AbstractServiceProvider;

final class ReferencesServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(TargetRegistry::class, function ($container) {
            $registry = new TargetRegistry;

            $registry->add($container->make(DiscussionTarget::class));
            $registry->add($container->make(PostTarget::class));

            return $registry;
        });

        // Order decides which extractor's origin a row is stamped with when
        // more than one finds the same target. A pasted address is the plainer
        // fact, so it wins over a mention of the same thing.
        $this->container->singleton('datlechin-references.extractors', fn ($container) => [
            $container->make(UrlReferenceExtractor::class),
            $container->make(ShortRefExtractor::class),
            $container->make(MentionReferenceExtractor::class),
        ]);

        $this->container->when(ReferenceSyncer::class)
            ->needs('$extractors')
            ->give(fn ($container) => $container->make('datlechin-references.extractors'));
    }

    public function boot(): void
    {
        // Registering the built-ins in register() would run before any
        // extender had a chance to add its own, and the morph map has to hold
        // every type before the first query touches it.
        $this->container->make(TargetRegistry::class);
    }
}
