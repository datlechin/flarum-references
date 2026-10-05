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

interface ExtractorInterface
{
    public function origin(): ReferenceOrigin;

    /**
     * Only parse-time attributes are readable here. `Post::$parsed_content` is
     * frozen, so anything a render pass works out later is not in it.
     *
     * Whether a target sits in the post's own discussion is not this method's
     * question: the syncer resolves every target and drops those itself.
     *
     * @return list<ExtractedReference>
     */
    public function extract(ParsedContent $content): array;
}
