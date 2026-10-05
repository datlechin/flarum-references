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

    public function extract(ParsedContent $content): array
    {
        if (! $this->config->extractShortReferences()) {
            return [];
        }

        $found = [];

        // The filter chain already refused any id without a discussion behind
        // it, so what survived into the stored XML needs no second look. One
        // inside a quote was typed by whoever is being quoted.
        foreach ($content->attributeValues(ConfigureDiscussionReferences::TAG_NAME, 'id') as $id) {
            if (! ctype_digit($id)) {
                continue;
            }

            $reference = ExtractedReference::make(Reference::TARGET_DISCUSSION, (int) $id, $this->origin());
            $found[$reference->key()] = $reference;
        }

        return array_values($found);
    }
}
