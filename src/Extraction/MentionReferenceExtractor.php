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

use Datlechin\References\Reference;
use Datlechin\References\ReferenceOrigin;
use Datlechin\References\Settings\Config;
use s9e\TextFormatter\Utils;

final class MentionReferenceExtractor implements ExtractorInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function origin(): ReferenceOrigin
    {
        return ReferenceOrigin::Mention;
    }

    public function extract(string $parsedXml): array
    {
        if (! $this->config->extractMentionReferences() || ! str_contains($parsedXml, '<POSTMENTION')) {
            return [];
        }

        $found = [];

        foreach (Utils::getAttributeValues($parsedXml, 'POSTMENTION', 'id') as $id) {
            if (! ctype_digit($id)) {
                continue;
            }

            $reference = ExtractedReference::make(Reference::TARGET_POST, (int) $id, $this->origin());
            $found[$reference->key()] = $reference;
        }

        return array_values($found);
    }
}
