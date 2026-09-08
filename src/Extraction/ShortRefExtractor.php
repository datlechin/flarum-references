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

use Datlechin\References\Formatter\ConfigureDiscussionReferences;
use Datlechin\References\Reference;
use Datlechin\References\ReferenceOrigin;
use Datlechin\References\Settings\Config;
use s9e\TextFormatter\Utils;

final class ShortRefExtractor implements ExtractorInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function origin(): ReferenceOrigin
    {
        return ReferenceOrigin::ShortRef;
    }

    public function extract(string $parsedXml): array
    {
        $tag = ConfigureDiscussionReferences::TAG_NAME;

        if (! $this->config->extractShortReferences() || ! str_contains($parsedXml, '<'.$tag)) {
            return [];
        }

        $found = [];

        // The filter chain already refused any id without a discussion behind
        // it, so what survived into the stored XML needs no second look.
        foreach (Utils::getAttributeValues($parsedXml, $tag, 'id') as $id) {
            if (! ctype_digit($id)) {
                continue;
            }

            $reference = ExtractedReference::make(Reference::TARGET_DISCUSSION, (int) $id, $this->origin());
            $found[$reference->key()] = $reference;
        }

        return array_values($found);
    }
}
