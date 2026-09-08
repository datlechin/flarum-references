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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The gap this extension exists to close: a pasted address is recorded, where
 * core only relabels it at render and stores nothing.
 */
class RecordsALinkToADiscussionTest extends TestCase
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
        ]);
    }

    #[Test]
    public function a_pasted_discussion_address_becomes_a_reference(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertSame(Reference::TARGET_DISCUSSION, $references[0]->target_type);
        $this->assertSame(1, $references[0]->target_id);
        $this->assertSame(1, $references[0]->target_discussion_id);
        $this->assertSame(2, $references[0]->source_discussion_id);
        $this->assertSame(ReferenceOrigin::Url, $references[0]->origin);
    }

    #[Test]
    public function an_address_with_a_slug_resolves_to_the_same_discussion(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1-seasoning-a-cast-iron-pan');

        $this->assertSame(1, $this->references()[0]->target_id);
    }

    #[Test]
    public function the_same_discussion_linked_twice_is_recorded_once(): void
    {
        $this->reply(2, $this->forum().'/d/1 and again '.$this->forum().'/d/1-seasoning-a-cast-iron-pan');

        $this->assertCount(1, $this->references());
    }

    #[Test]
    public function an_address_on_another_host_is_not_a_reference(): void
    {
        $this->reply(2, 'See http://example.com/d/1');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function a_link_to_a_discussion_that_does_not_exist_records_nothing(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/999');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function the_ranking_counter_follows_the_rows(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertSame(1, $this->referencesCount(1));
    }

    #[Test]
    public function editing_the_link_out_of_a_post_removes_the_reference(): void
    {
        $response = $this->reply(2, 'See '.$this->forum().'/d/1');

        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertCount(1, $this->references());

        $this->edit($postId, 'Never mind.');

        $this->assertCount(0, $this->references());
        $this->assertSame(0, $this->referencesCount(1));
    }
}
