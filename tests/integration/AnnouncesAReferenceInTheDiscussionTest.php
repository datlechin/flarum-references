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

use Datlechin\References\Post\ReferencedEventPost;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The optional line in the referenced discussion: "Alice referenced this
 * discussion in <where>".
 */
class AnnouncesAReferenceInTheDiscussionTest extends TestCase
{
    use RecordsReferences;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-references');
        $this->setting('datlechin-references.event_post_enabled', '1');

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            Discussion::class => [
                $this->discussionRow(1, 'Seasoning a cast iron pan'),
                $this->discussionRow(2, 'Rust, and what to do about it'),
                $this->discussionRow(3, 'Which oil smokes least'),
            ],
        ]);
    }

    /**
     * @return list<ReferencedEventPost>
     */
    protected function announcements(): array
    {
        /** @var list<ReferencedEventPost> */
        return Post::query()->where('discussion_id', 1)->where('type', ReferencedEventPost::$type)->orderBy('id')->get()->all();
    }

    #[Test]
    public function two_citations_by_one_member_share_a_line(): void
    {
        $first = (int) json_decode((string) $this->reply(2, 'See '.$this->forum().'/d/1', 1)->getBody(), true)['data']['id'];
        $second = (int) json_decode((string) $this->reply(3, 'Also '.$this->forum().'/d/1', 1)->getBody(), true)['data']['id'];

        $announcements = $this->announcements();

        $this->assertCount(1, $announcements);
        $this->assertSame([$first, $second], $announcements[0]->sourcePostIds());
    }

    /**
     * Merged, the line named whoever cited first and credited them with the
     * second member's citation too.
     */
    #[Test]
    public function citations_by_two_members_get_a_line_each(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1', 1);
        $this->reply(3, 'Also '.$this->forum().'/d/1', 2);

        $announcements = $this->announcements();

        $this->assertCount(2, $announcements);
        $this->assertSame(1, $announcements[0]->user_id);
        $this->assertSame(2, $announcements[1]->user_id);
    }

    #[Test]
    public function the_line_carries_only_the_citing_posts_the_reader_may_open(): void
    {
        $postId = (int) json_decode((string) $this->reply(2, 'See '.$this->forum().'/d/1', 1)->getBody(), true)['data']['id'];
        $announcement = $this->announcements()[0];

        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/'.$announcement->id, ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame([['type' => 'posts', 'id' => (string) $postId]], $body['data']['relationships']['referenceSources']['data']);

        $this->database()->table('discussions')->where('id', 2)->update(['is_private' => true]);

        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/'.$announcement->id, ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame([], $body['data']['relationships']['referenceSources']['data']);
    }

    #[Test]
    public function a_citation_from_a_hidden_discussion_is_not_announced(): void
    {
        $this->database()->table('discussions')->where('id', 2)->update(['hidden_at' => '2026-01-02 00:00:00', 'hidden_user_id' => 1]);

        $response = $this->reply(2, 'See '.$this->forum().'/d/1', 1);

        $this->assertSame(201, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(1, $this->references());
        $this->assertCount(0, $this->announcements());
    }
}
