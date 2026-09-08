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
 * A classification is the moderator's, not the author's. Nothing the author
 * does to their own post afterwards should throw it away, and neither should a
 * moderation action that is meant to be reversible.
 */
class KeepsWhatAModeratorSaidAboutAReferenceTest extends TestCase
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

    protected function classify(): int
    {
        $response = $this->reply(2, 'See '.$this->forum().'/d/1');
        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        Reference::query()->where('source_post_id', $postId)->update([
            'relation_type' => RelationType::DuplicateOf->value,
            'note' => 'Same question, answered there.',
        ]);

        return $postId;
    }

    #[Test]
    public function editing_the_link_out_leaves_a_classified_reference_alone(): void
    {
        $postId = $this->classify();

        $this->edit($postId, 'Never mind.');

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertSame(RelationType::DuplicateOf, $references[0]->relation_type);
    }

    #[Test]
    public function hiding_the_post_keeps_the_reference_and_what_was_said_about_it(): void
    {
        $postId = $this->classify();

        $this->send($this->request('PATCH', '/api/posts/'.$postId, [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['isHidden' => true]]],
        ]));

        $references = $this->references();

        $this->assertCount(1, $references);
        $this->assertSame(RelationType::DuplicateOf, $references[0]->relation_type);
        $this->assertSame('Same question, answered there.', $references[0]->note);
    }

    #[Test]
    public function a_hidden_post_stops_showing_its_references_to_a_reader(): void
    {
        $postId = $this->classify();

        $this->send($this->request('PATCH', '/api/posts/'.$postId, [
            'authenticatedAs' => 1,
            'json' => ['data' => ['attributes' => ['isHidden' => true]]],
        ]));

        // The row survives, but a reference is only as visible as its two ends.
        $this->assertSame(0, Reference::whereVisibleTo(new \Flarum\User\Guest)->count());
    }
}
