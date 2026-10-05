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
use Flarum\Testing\integration\TestCase;
use Illuminate\Database\ConnectionInterface;
use PHPUnit\Framework\Attributes\Test;

/**
 * An install upgrading from 1.0.0 has every reply recorded as a reference, and
 * a counter that counted rows. The migration takes both back.
 */
class CleansUpWhatTheFirstReleaseRecordedTest extends TestCase
{
    use RecordsReferences;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-references');

        $post = fn (int $id, int $discussionId, int $number) => [
            'id' => $id,
            'discussion_id' => $discussionId,
            'number' => $number,
            'created_at' => '2026-01-01 00:00:00',
            'user_id' => 1,
            'type' => 'comment',
            'content' => '<t>.</t>',
            'is_private' => false,
        ];

        $row = fn (int $sourcePostId, int $sourceDiscussionId, int $targetDiscussionId, string $relation = 'references') => [
            'source_post_id' => $sourcePostId,
            'source_discussion_id' => $sourceDiscussionId,
            'target_type' => 'discussions',
            'target_id' => $targetDiscussionId,
            'target_discussion_id' => $targetDiscussionId,
            'relation_type' => $relation,
            'origin' => 'url',
            'created_at' => '2026-01-01 00:00:00',
        ];

        $this->prepareDatabase([
            Discussion::class => [
                ['references_count' => 9] + $this->discussionRow(1, 'Seasoning a cast iron pan', 1),
                $this->discussionRow(2, 'Rust, and what to do about it', 1),
            ],
            Post::class => [$post(10, 1, 1), $post(11, 1, 2), $post(20, 2, 1), $post(21, 2, 2)],
            Reference::class => [
                $row(11, 1, 1),
                ['target_type' => 'posts', 'target_id' => 10] + $row(11, 1, 1, 'duplicate_of'),
                $row(20, 2, 1),
                ['target_type' => 'posts', 'target_id' => 10] + $row(21, 2, 1),
            ],
        ]);
    }

    #[Test]
    public function the_rows_inside_one_discussion_go_and_the_counter_counts_discussions(): void
    {
        $migration = require __DIR__.'/../../migrations/2026_10_05_000000_remove_references_within_one_discussion.php';

        $migration['up']($this->app()->getContainer()->make(ConnectionInterface::class)->getSchemaBuilder());

        $this->assertSame([2, 2], $this->references()->pluck('source_discussion_id')->all());
        $this->assertSame(1, $this->referencesCount(1));
    }
}
