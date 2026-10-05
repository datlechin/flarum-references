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

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * A post's stored XML, parsed once and shared by every extractor.
 *
 * `s9e\TextFormatter\Utils::getAttributeValues()` reads a tag wherever it sits,
 * which cannot tell what the writer said from what they quoted. Quoting a post
 * copies its links along with its words, and recording those again made the
 * quoter cite whatever the quoted author had cited.
 */
final class ParsedContent
{
    private ?DOMXPath $xpath = null;

    public function __construct(
        public readonly string $xml,
    ) {
    }

    /**
     * @return list<string>
     */
    public function attributeValues(string $tag, string $attribute, bool $includeQuoted = false): array
    {
        if (! str_contains($this->xml, '<'.$tag)) {
            return [];
        }

        $xpath = $this->xpath();

        if ($xpath === null) {
            return [];
        }

        $expression = '//'.$tag.($includeQuoted ? '' : '[not(ancestor::QUOTE)]');
        $nodes = $xpath->query($expression);

        if ($nodes === false) {
            return [];
        }

        $values = [];

        foreach ($nodes as $node) {
            if ($node instanceof DOMElement && $node->hasAttribute($attribute)) {
                $values[] = $node->getAttribute($attribute);
            }
        }

        return $values;
    }

    private function xpath(): ?DOMXPath
    {
        if ($this->xpath !== null) {
            return $this->xpath;
        }

        $document = new DOMDocument;

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($this->xml, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $this->xpath = new DOMXPath($document) : null;
    }
}
