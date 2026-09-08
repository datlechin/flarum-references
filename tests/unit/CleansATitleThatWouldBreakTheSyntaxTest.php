<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Tests\unit;

use Datlechin\References\Formatter\UnparseDiscussionReferences;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * `@"Title"#d123` ends with the same shape a title is allowed to contain, so a
 * title carrying it would cut the regex short on the next parse. Titles run
 * into this far more often than usernames do, which is why mentions guards its
 * display names the same way.
 */
class CleansATitleThatWouldBreakTheSyntaxTest extends TestCase
{
    #[Test]
    #[DataProvider('titles')]
    public function a_title_is_safe_to_put_between_quotes(string $title, string $expected): void
    {
        $this->assertSame($expected, UnparseDiscussionReferences::sanitiseTitle($title));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function titles(): array
    {
        return [
            'an ordinary title is untouched' => [
                'Seasoning a cast iron pan',
                'Seasoning a cast iron pan',
            ],
            'a quote on its own is fine' => [
                'The "best" way to season a pan',
                'The "best" way to season a pan',
            ],
            'the discussion shape is replaced' => [
                'Read this"#d12 first',
                'Read this_ first',
            ],
            'the user shape is replaced too' => [
                'Ask them"#42 about it',
                'Ask them_ about it',
            ],
            'the post shape is replaced too' => [
                'See this"#p7 for context',
                'See this_ for context',
            ],
            'a hash with no digits is left alone' => [
                'A title with "#hashtag in it',
                'A title with "#hashtag in it',
            ],
        ];
    }
}
