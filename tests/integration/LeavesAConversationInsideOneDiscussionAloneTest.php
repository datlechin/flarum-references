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
 * A reference is one discussion pointing at another. Pressing Reply writes a
 * post mention to a post in the same discussion, and that is a conversation,
 * not a citation: Mentions already lists it under the post replied to.
 * Recording it put every reply in the reference list and fired a second
 * notification for something Mentions had announced.
 */
class LeavesAConversationInsideOneDiscussionAloneTest extends TestCase
{
    use RecordsReferences;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('flarum-markdown', 'flarum-mentions', 'datlechin-references');

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
                    'number' => 1,
                    'created_at' => '2026-01-01 00:00:00',
                    'user_id' => 1,
                    'type' => 'comment',
                    'content' => '<t>Start with a thin coat of oil.</t>',
                    'is_private' => false,
                ],
            ],
        ]);
    }

    #[Test]
    public function a_reply_to_a_post_in_the_same_discussion_is_not_a_reference(): void
    {
        $response = $this->reply(1, '@"admin"#p10 How thin is thin?');

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertStringContainsString('<POSTMENTION', (string) Post::query()->latest('id')->value('content'));
        $this->assertCount(0, $this->references());
        $this->assertSame(0, $this->referencesCount(1));
    }

    #[Test]
    public function the_post_replied_to_lists_no_reference(): void
    {
        $this->reply(1, '@"admin"#p10 How thin is thin?');

        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/10', ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame(0, $body['data']['attributes']['referencedByCount']);
        $this->assertSame([], $body['data']['relationships']['referencedBy']['data'] ?? []);
    }

    #[Test]
    public function a_mention_of_a_post_in_another_discussion_is_a_reference(): void
    {
        $this->reply(2, '@"admin"#p10 explains the oiling step.');

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertSame(Reference::TARGET_POST, $references[0]->target_type);
        $this->assertSame(10, $references[0]->target_id);
        $this->assertSame(1, $references[0]->target_discussion_id);
        $this->assertSame(ReferenceOrigin::Mention, $references[0]->origin);
    }

    /**
     * Mentions lists it under the post already, as a reply, so the post's own
     * reference list leaves it out. The discussion still counts it.
     */
    #[Test]
    public function a_mention_from_another_discussion_is_listed_under_the_post_by_mentions_alone(): void
    {
        $this->reply(2, '@"admin"#p10 explains the oiling step.', 1);
        $this->reply(2, 'And the photos: '.$this->forum().'/d/1/1', 1);

        $post = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/10', ['authenticatedAs' => 2])
        )->getBody(), true);

        $discussion = json_decode((string) $this->send(
            $this->request('GET', '/api/discussions/1', ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame(1, $post['data']['attributes']['referencedByCount']);
        $this->assertCount(1, $post['data']['relationships']['referencedBy']['data']);
        $this->assertSame(1, $discussion['data']['attributes']['referencedByCount']);
    }

    #[Test]
    public function a_link_to_a_post_in_the_same_discussion_is_not_a_reference(): void
    {
        $this->reply(1, 'As the opening post says: '.$this->forum().'/d/1/1');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function a_link_to_the_same_discussion_is_not_a_reference(): void
    {
        $this->reply(1, 'Back to the top: '.$this->forum().'/d/1-seasoning-a-cast-iron-pan');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function a_typed_reference_to_the_same_discussion_is_not_a_reference(): void
    {
        $this->reply(1, 'Still on @"Seasoning a cast iron pan"#d1');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function editing_a_reply_keeps_it_out_of_the_list(): void
    {
        $postId = (int) json_decode((string) $this->reply(1, '@"admin"#p10 How thin?')->getBody(), true)['data']['id'];

        $this->edit($postId, '@"admin"#p10 How thin, exactly?');

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function a_link_quoted_from_someone_else_is_theirs_not_the_quoters(): void
    {
        $this->reply(2, "> Someone wrote: see {$this->forum()}/d/1\n\nI disagree with that.");

        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function a_link_written_beside_a_quote_is_still_recorded(): void
    {
        $this->reply(2, "> Someone wrote something.\n\nThe answer is in {$this->forum()}/d/1");

        $this->assertCount(1, $this->references());
    }

    #[Test]
    public function a_moderator_cannot_add_a_reference_from_a_discussion_to_itself(): void
    {
        $response = $this->send(
            $this->request('POST', '/api/post-references', [
                'authenticatedAs' => 1,
                'json' => [
                    'data' => [
                        'type' => 'post-references',
                        'attributes' => ['targetType' => 'discussions', 'targetId' => 1],
                        'relationships' => [
                            'sourceDiscussion' => ['data' => ['type' => 'discussions', 'id' => '1']],
                        ],
                    ],
                ],
            ])
        );

        $this->assertSame(422, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(0, $this->references());
    }
}
