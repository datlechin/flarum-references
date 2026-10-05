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
use Datlechin\References\RelationType;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A discussion is cited by discussions. One thread that links here three times
 * is one entry in the sidebar, one in the full list, and one in the ranking.
 */
class ListsEachCitingDiscussionOnceTest extends TestCase
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
                $this->discussionRow(3, 'Which oil smokes least'),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    protected function get(string $path, array $query = []): array
    {
        return json_decode((string) $this->send(
            $this->request('GET', $path, ['authenticatedAs' => 2])->withQueryParams($query)
        )->getBody(), true);
    }

    /**
     * As the administrator, who is exempt from the flood gate that refuses a
     * member's second post inside ten seconds.
     */
    protected function citeThreeTimesFromOneDiscussion(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1', 1);
        $this->reply(2, 'Again, '.$this->forum().'/d/1', 1);
        $this->reply(2, 'And once more: '.$this->forum().'/d/1', 1);
    }

    #[Test]
    public function the_sidebar_names_a_citing_discussion_once(): void
    {
        $this->citeThreeTimesFromOneDiscussion();
        $this->reply(3, 'Related: '.$this->forum().'/d/1', 1);

        $body = $this->get('/api/discussions/1');

        $this->assertCount(4, $this->references());
        $this->assertSame(2, $body['data']['attributes']['referencedByCount']);
        $this->assertCount(2, $body['data']['relationships']['referencedBy']['data']);
    }

    #[Test]
    public function the_citing_side_counts_the_discussion_it_cites_once(): void
    {
        $this->citeThreeTimesFromOneDiscussion();

        $body = $this->get('/api/discussions/2');

        $this->assertSame(1, $body['data']['attributes']['outgoingReferencesCount']);
        $this->assertCount(1, $body['data']['relationships']['outgoingReferences']['data']);
    }

    #[Test]
    public function the_full_list_groups_the_way_the_sidebar_does(): void
    {
        $this->citeThreeTimesFromOneDiscussion();
        $this->reply(3, 'Related: '.$this->forum().'/d/1', 1);

        $incoming = $this->get('/api/post-references', ['filter' => ['incoming' => '1']]);
        $outgoing = $this->get('/api/post-references', ['filter' => ['outgoing' => '2']]);

        $this->assertCount(2, $incoming['data']);
        $this->assertCount(1, $outgoing['data']);
    }

    #[Test]
    public function the_row_standing_for_a_discussion_is_the_one_a_moderator_classified(): void
    {
        $this->citeThreeTimesFromOneDiscussion();

        $classified = Reference::query()->orderBy('id')->firstOrFail();
        $classified->relation_type = RelationType::DuplicateOf;
        $classified->save();

        $body = $this->get('/api/discussions/1');

        $this->assertSame((string) $classified->id, $body['data']['relationships']['referencedBy']['data'][0]['id']);
    }

    #[Test]
    public function the_ranking_counts_discussions_not_links(): void
    {
        $this->citeThreeTimesFromOneDiscussion();

        $this->assertSame(1, $this->referencesCount(1));

        $this->reply(3, 'Related: '.$this->forum().'/d/1', 1);

        $this->assertSame(2, $this->referencesCount(1));
    }

    #[Test]
    public function a_hidden_post_leaves_the_ranking_until_it_is_restored(): void
    {
        $postId = (int) json_decode((string) $this->reply(2, 'See '.$this->forum().'/d/1')->getBody(), true)['data']['id'];

        $this->hide($postId, true);
        $this->assertSame(0, $this->referencesCount(1));

        $this->hide($postId, false);
        $this->assertSame(1, $this->referencesCount(1));
    }

    #[Test]
    public function a_hidden_discussion_leaves_the_ranking_until_it_is_restored(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->hideDiscussion(2, true);
        $this->assertSame(0, $this->referencesCount(1));

        $this->hideDiscussion(2, false);
        $this->assertSame(1, $this->referencesCount(1));
    }

    #[Test]
    public function a_discussion_list_does_not_carry_the_sidebar(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $body = $this->get('/api/discussions');

        foreach ($body['data'] as $discussion) {
            $this->assertArrayNotHasKey('referencedByCount', $discussion['attributes']);
            $this->assertArrayNotHasKey('referencedBy', $discussion['relationships'] ?? []);
        }
    }

    #[Test]
    public function the_search_gambits_follow_a_link_to_a_post_as_well(): void
    {
        $this->reply(1, 'An opening thought.', 1);
        $number = (int) $this->database()->table('posts')->where('discussion_id', 1)->max('number');

        $this->reply(2, 'See '.$this->forum().'/d/1/'.$number, 1);

        $citing = $this->get('/api/discussions', ['filter' => ['references' => '1']]);
        $cited = $this->get('/api/discussions', ['filter' => ['referencedBy' => '2']]);

        $this->assertSame(['2'], array_column($citing['data'], 'id'));
        $this->assertSame(['1'], array_column($cited['data'], 'id'));
    }

    protected function hide(int $postId, bool $hidden): void
    {
        $this->send($this->request('PATCH', '/api/posts/'.$postId, [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['isHidden' => $hidden]]],
        ]));
    }

    protected function hideDiscussion(int $discussionId, bool $hidden): void
    {
        $this->send($this->request('PATCH', '/api/discussions/'.$discussionId, [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['isHidden' => $hidden]]],
        ]));
    }
}
