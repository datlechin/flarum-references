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
use Psr\Http\Message\ResponseInterface;

/**
 * Through the API the forum uses, because 1.0.0 had a resource that could do
 * all of this and no screen that ever asked it to.
 */
class LetsAModeratorCorrectAReferenceTest extends TestCase
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

    protected function addByHand(int $from, int $to): ResponseInterface
    {
        return $this->send($this->request('POST', '/api/post-references', [
            'authenticatedAs' => 1,
            'json' => [
                'data' => [
                    'type' => 'post-references',
                    'attributes' => ['targetType' => 'discussions', 'targetId' => $to, 'relationType' => 'see_also'],
                    'relationships' => ['sourceDiscussion' => ['data' => ['type' => 'discussions', 'id' => (string) $from]]],
                ],
            ],
        ]));
    }

    /**
     * @param array<string, mixed> $attributes
     */
    protected function patch(int $id, array $attributes, int $as = 1): ResponseInterface
    {
        return $this->send($this->request('PATCH', '/api/post-references/'.$id, [
            'authenticatedAs' => $as,
            'json' => ['data' => ['type' => 'post-references', 'id' => (string) $id, 'attributes' => $attributes]],
        ]));
    }

    #[Test]
    public function a_reference_added_by_hand_can_be_taken_away_again(): void
    {
        $id = (int) json_decode((string) $this->addByHand(2, 1)->getBody(), true)['data']['id'];

        $this->assertSame(1, $this->referencesCount(1));

        $response = $this->send($this->request('DELETE', '/api/post-references/'.$id, ['authenticatedAs' => 1]));

        $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
        $this->assertCount(0, $this->references());
        $this->assertSame(0, $this->referencesCount(1));
    }

    #[Test]
    public function a_reference_a_post_wrote_is_retracted_by_editing_the_post_instead(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');
        $id = (int) $this->references()[0]->id;

        $response = $this->send($this->request('DELETE', '/api/post-references/'.$id, ['authenticatedAs' => 1]));

        $this->assertSame(403, $response->getStatusCode());
        $this->assertCount(1, $this->references());
    }

    #[Test]
    public function a_moderator_can_classify_and_annotate_a_reference(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');
        $id = (int) $this->references()[0]->id;

        $response = $this->patch($id, ['relationType' => 'duplicate_of', 'note' => '  Same question.  ']);

        $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());

        $reference = Reference::query()->findOrFail($id);
        $this->assertSame(RelationType::DuplicateOf, $reference->relation_type);
        $this->assertSame('Same question.', $reference->note);

        $this->patch($id, ['note' => '']);

        $this->assertNull(Reference::query()->findOrFail($id)->note);
    }

    #[Test]
    public function a_member_without_the_permission_cannot(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');
        $id = (int) $this->references()[0]->id;

        $this->assertSame(403, $this->patch($id, ['relationType' => 'duplicate_of'], as: 2)->getStatusCode());
    }

    #[Test]
    public function the_row_says_what_the_reader_may_do_with_it(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');
        $extracted = (int) $this->references()[0]->id;
        $manual = (int) json_decode((string) $this->addByHand(2, 1)->getBody(), true)['data']['id'];

        $attributes = function (int $id, int $as): array {
            return json_decode((string) $this->send(
                $this->request('GET', '/api/post-references/'.$id, ['authenticatedAs' => $as])
            )->getBody(), true)['data']['attributes'];
        };

        $this->assertTrue($attributes($extracted, 1)['canEdit']);
        $this->assertFalse($attributes($extracted, 1)['canDelete']);
        $this->assertTrue($attributes($manual, 1)['canDelete']);
        $this->assertFalse($attributes($manual, 2)['canEdit']);
        $this->assertFalse($attributes($manual, 2)['canDelete']);
    }

    #[Test]
    public function the_same_pair_cannot_be_added_by_hand_twice(): void
    {
        $this->addByHand(2, 1);

        $response = $this->addByHand(2, 1);

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame('/data/attributes/targetId', json_decode((string) $response->getBody(), true)['errors'][0]['source']['pointer']);
    }
}
