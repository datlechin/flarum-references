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
 * A discussion is at both ends of the graph: things point at it, and it points
 * at things. The incoming side hangs off the denormalised host column so it
 * catches references to any post inside the discussion, not just to the
 * discussion itself.
 */
class ShowsBothDirectionsAtADiscussionTest extends TestCase
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

    /**
     * @return array<string, mixed>
     */
    protected function show(int $id): array
    {
        return json_decode((string) $this->send(
            $this->request('GET', '/api/discussions/'.$id, ['authenticatedAs' => 2])
        )->getBody(), true);
    }

    #[Test]
    public function the_cited_discussion_counts_what_points_at_it(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertSame(1, $this->show(1)['data']['attributes']['referencedByCount']);
        $this->assertSame(0, $this->show(1)['data']['attributes']['outgoingReferencesCount']);
    }

    #[Test]
    public function the_citing_discussion_counts_what_it_points_at(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertSame(1, $this->show(2)['data']['attributes']['outgoingReferencesCount']);
        $this->assertSame(0, $this->show(2)['data']['attributes']['referencedByCount']);
    }

    /**
     * The sidebar draws from what the page already carries and never asks for
     * more, so a direction missing from the default includes arrives as a count
     * with no rows behind it and its section never renders.
     */
    #[Test]
    public function both_directions_come_back_without_being_asked_for(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertCount(1, $this->show(1)['data']['relationships']['referencedBy']['data']);
        $this->assertCount(1, $this->show(2)['data']['relationships']['outgoingReferences']['data']);
    }

    #[Test]
    public function the_relation_types_the_picker_may_offer_come_from_the_server(): void
    {
        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/', ['authenticatedAs' => 2])
        )->getBody(), true);

        $this->assertSame(
            ['references', 'duplicate_of', 'see_also', 'supersedes', 'answers'],
            $body['data']['attributes']['datlechin-references.relationTypes']
        );
    }
}
