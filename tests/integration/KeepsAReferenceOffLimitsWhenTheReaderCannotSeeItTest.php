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
 * The title is read from the database at render, which is what lets a renamed
 * discussion still read correctly years later. Unscoped, that same lookup hands
 * the title of anything to anyone, and a post full of invented ids reads them
 * back in bulk.
 */
class KeepsAReferenceOffLimitsWhenTheReaderCannotSeeItTest extends TestCase
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
                // Hidden and authored by the admin. Core grants a hidden
                // discussion to its author and to anyone holding
                // `discussion.hide`, so the admin may open it and user 2 may
                // not. `is_private` would not do: without an extension claiming
                // `viewPrivate` nobody can see it, admin included.
                array_merge($this->discussionRow(1, 'Seasoning a cast iron pan', 1), [
                    'hidden_at' => '2026-01-02 00:00:00',
                ]),
                $this->discussionRow(2, 'Rust, and what to do about it'),
            ],
        ]);
    }

    protected function contentHtml(int $postId, int $actor): string
    {
        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/posts/'.$postId, ['authenticatedAs' => $actor])
        )->getBody(), true);

        return $body['data']['attributes']['contentHtml'] ?? '';
    }

    #[Test]
    public function a_private_discussions_title_does_not_reach_a_reader_who_cannot_open_it(): void
    {
        // Written by the admin, who may see it, so the tag itself is valid.
        $response = $this->reply(2, 'See @"Seasoning a cast iron pan"#d1', 1);
        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertStringNotContainsString('Seasoning a cast iron pan', $this->contentHtml($postId, 2));
    }

    #[Test]
    public function the_reader_who_may_open_it_still_sees_the_title(): void
    {
        $response = $this->reply(2, 'See @"Seasoning a cast iron pan"#d1', 1);
        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertStringContainsString('Seasoning a cast iron pan', $this->contentHtml($postId, 1));
    }

    /**
     * The parse side, so an id the writer cannot reach never becomes a stored
     * tag in the first place.
     */
    #[Test]
    public function a_writer_who_cannot_see_the_discussion_cannot_make_a_tag_for_it(): void
    {
        $response = $this->reply(2, 'See @"Anything at all"#d1', 2);
        $postId = (int) json_decode((string) $response->getBody(), true)['data']['id'];

        $this->assertStringNotContainsString('Seasoning a cast iron pan', $this->contentHtml($postId, 1));
    }
}
