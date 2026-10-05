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

/**
 * A post mention is only a reference when it reaches into another discussion.
 * Reply writes one to a post in the same discussion, which is a conversation
 * Mentions already lists under the post replied to; the syncer drops those.
 */
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

    /**
     * Quoted mentions count. Quoting a post opens with a mention of it, and
     * that mention is the quoter's own attribution, not the quoted author's.
     */
    public function extract(ParsedContent $content): array
    {
        if (! $this->config->extractMentionReferences()) {
            return [];
        }

        $found = [];

        foreach ($content->attributeValues('POSTMENTION', 'id', includeQuoted: true) as $id) {
            if (! ctype_digit($id)) {
                continue;
            }

            $reference = ExtractedReference::make(Reference::TARGET_POST, (int) $id, $this->origin());
            $found[$reference->key()] = $reference;
        }

        return array_values($found);
    }
}
