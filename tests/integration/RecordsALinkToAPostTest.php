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
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * An address naming a post number resolves to that post, and the discussion it
 * lives in is recorded alongside so the discussion's own list still finds it.
 */
class RecordsALinkToAPostTest extends TestCase
{
    use RecordsReferences;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-references');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            Discussion::class => [
                $this->discussionRow(1, 'Seasoning a cast iron pan'),
                $this->discussionRow(2, 'Rust, and what to do about it'),
            ],
            Post::class => [
                [
                    'id' => 10,
                    'discussion_id' => 1,
                    'number' => 3,
                    'created_at' => '2026-01-01 00:00:00',
                    'user_id' => 2,
                    'type' => 'comment',
                    'content' => '<r>The third one</r>',
                    'is_private' => false,
                ],
            ],
        ]);
    }

    #[Test]
    public function an_address_naming_a_post_number_records_that_post(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1/3');

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertSame(Reference::TARGET_POST, $references[0]->target_type);
        $this->assertSame(10, $references[0]->target_id);
        $this->assertSame(1, $references[0]->target_discussion_id);
    }

    #[Test]
    public function the_discussion_counter_still_moves_for_a_post_target(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1/3');

        $this->assertSame(1, $this->referencesCount(1));
    }

    #[Test]
    public function a_post_number_that_does_not_exist_records_nothing(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1/99');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function an_address_naming_a_position_that_is_not_a_number_is_left_alone(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1/near-3');

        $this->assertCount(0, $this->references());
    }

    /**
     * The read side, not just the row: a post is stored under the `posts` morph
     * alias but loaded as a subclass such as CommentPost, so a relation keyed on
     * the model's own morph class matches nothing and the backlinks never show.
     */
    #[Test]
    public function the_cited_post_serves_the_references_pointing_at_it(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1/3');

        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/10', ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame(1, $body['data']['attributes']['referencedByCount']);
        $this->assertCount(1, $body['data']['relationships']['referencedBy']['data']);
    }
}
