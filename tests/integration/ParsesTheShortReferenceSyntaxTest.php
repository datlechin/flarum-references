<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Tests\integration;

use Datlechin\References\Reference;
use Datlechin\References\ReferenceOrigin;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `@"Title"#d123` lives in the slot core reserved with the lookahead
 * `(?!"#[a-z]{0,3}[0-9]+)` in every mention regex. Nothing here should depend
 * on tag priority or on which extension loaded first, and these tests run with
 * mentions and tags both enabled so a regression in that shows up.
 */
class ParsesTheShortReferenceSyntaxTest extends TestCase
{
    use RecordsReferences;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-tags', 'flarum-mentions', 'datlechin-references');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            Discussion::class => [
                $this->discussionRow(1, 'Seasoning a cast iron pan'),
                $this->discussionRow(2, 'Rust, and what to do about it'),
            ],
        ]);
    }

    #[Test]
    public function a_typed_reference_is_recorded(): void
    {
        $this->reply(2, 'As covered in @"Seasoning a cast iron pan"#d1');

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertSame(Reference::TARGET_DISCUSSION, $references[0]->target_type);
        $this->assertSame(1, $references[0]->target_id);
        $this->assertSame(ReferenceOrigin::ShortRef, $references[0]->origin);
    }

    #[Test]
    public function the_stored_xml_carries_the_tag_and_not_the_raw_text(): void
    {
        $response = $this->reply(2, 'As covered in @"Seasoning a cast iron pan"#d1');
        $post = Post::query()->find((int) json_decode((string) $response->getBody(), true)['data']['id']);

        $this->assertStringContainsString('<DISCUSSIONREFERENCE', (string) $post?->parsed_content);
    }

    #[Test]
    public function a_reference_to_a_discussion_that_does_not_exist_stays_plain_text(): void
    {
        $response = $this->reply(2, 'As covered in @"Nothing here"#d999');
        $post = Post::query()->find((int) json_decode((string) $response->getBody(), true)['data']['id']);

        $this->assertStringNotContainsString('<DISCUSSIONREFERENCE', (string) $post?->parsed_content);
        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function editing_a_post_gives_the_writer_the_syntax_back_with_the_current_title(): void
    {
        $response = $this->reply(2, 'As covered in @"Seasoning a cast iron pan"#d1');
        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        Discussion::query()->where('id', 1)->update(['title' => 'Seasoning, revisited']);

        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/'.$postId, ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame('As covered in @"Seasoning, revisited"#d1', $body['data']['attributes']['content']);
    }

    #[Test]
    public function a_tag_mention_in_the_same_post_still_works(): void
    {
        $this->prepareDatabase([
            'tags' => [
                ['id' => 1, 'name' => 'General', 'slug' => 'general', 'position' => 0, 'is_restricted' => false],
            ],
        ]);

        $response = $this->reply(2, 'See #general and @"Seasoning a cast iron pan"#d1');
        $post = Post::query()->find((int) json_decode((string) $response->getBody(), true)['data']['id']);

        $xml = (string) $post?->parsed_content;

        $this->assertStringContainsString('<TAGMENTION', $xml);
        $this->assertStringContainsString('<DISCUSSIONREFERENCE', $xml);
    }
}
