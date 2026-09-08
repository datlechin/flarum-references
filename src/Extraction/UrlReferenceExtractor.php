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
use Datlechin\References\Settings\Config;
use Datlechin\References\Target\TargetRegistry;
use s9e\TextFormatter\Utils;

final class UrlReferenceExtractor implements ExtractorInterface
{
    public function __construct(
        private ForumUrl $forumUrl,
        private TargetRegistry $targets,
        private Config $config,
    ) {
    }

    public function origin(): ReferenceOrigin
    {
        return ReferenceOrigin::Url;
    }

    public function extract(string $parsedXml): array
    {
        if (! $this->config->extractUrlReferences() || ! str_contains($parsedXml, '<URL')) {
            return [];
        }

        $found = [];

        foreach (Utils::getAttributeValues($parsedXml, 'URL', 'url') as $url) {
            $path = $this->forumUrl->path($url);

            if ($path === null || $path === '') {
                continue;
            }

            foreach ($this->targets->all() as $target) {
                $id = $target->matchUrl($path);

                if ($id !== null) {
                    $reference = ExtractedReference::make($target->key(), $id, $this->origin());
                    $found[$reference->key()] = $reference;

                    break;
                }
            }
        }

        return array_values($found);
    }
}
