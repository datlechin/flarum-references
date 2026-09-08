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

use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Deleting a discussion removes its posts through the database's own cascade,
 * which never reaches the API layer and so fires no per post event. Everything
 * that has to happen therefore happens in one listener on the discussion, and
 * these tests exist to catch anyone moving it back.
 */
class MarksAReferenceBrokenWhenItsTargetGoesTest extends TestCase
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

    protected function deleteDiscussion(int $id): void
    {
        $this->send($this->request('DELETE', '/api/discussions/'.$id, ['authenticatedAs' => 1]));
    }

    #[Test]
    public function the_row_is_kept_and_marked_rather_than_deleted(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->deleteDiscussion(1);

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertNotNull($references[0]->target_deleted_at);
        $this->assertTrue($references[0]->isBroken());
    }

    #[Test]
    public function marking_a_row_broken_takes_it_out_of_the_ranking_counter(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertSame(1, $this->referencesCount(1));

        $this->deleteDiscussion(1);

        // The counter is defined as the rows that still point at something,
        // which is what references:reconcile rebuilds it from, so marking has
        // to move it or the nightly job would silently disagree.
        $this->assertSame(0, $this->referencesCount(1));
    }

    /**
     * `Deleted` fires after the row is gone, and `source_post_id` cascades, so
     * by then the database has already removed everything the counter would
     * have been decremented from. SQLite hid this; MariaDB and Postgres do not.
     */
    #[Test]
    public function deleting_the_citing_post_brings_the_counter_back_down(): void
    {
        $response = $this->reply(2, 'See '.$this->forum().'/d/1');
        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertSame(1, $this->referencesCount(1));

        $this->send($this->request('DELETE', '/api/posts/'.$postId, ['authenticatedAs' => 1]));

        $this->assertCount(0, $this->references());
        $this->assertSame(0, $this->referencesCount(1));
    }

    #[Test]
    public function deleting_the_citing_discussion_removes_its_rows(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->deleteDiscussion(2);

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function the_counter_comes_back_down_when_the_citing_discussion_goes(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertSame(1, $this->referencesCount(1));

        $this->deleteDiscussion(2);

        $this->assertSame(0, $this->referencesCount(1));
    }
}
