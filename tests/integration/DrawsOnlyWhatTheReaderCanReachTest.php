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

use Datlechin\References\Service\ReferenceGraphQuery;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\User;
use PHPUnit\Framework\Attributes\Test;

class DrawsOnlyWhatTheReaderCanReachTest extends TestCase
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
                ['is_private' => true] + $this->discussionRow(2, 'A private thread', 1),
                $this->discussionRow(3, 'Rust, and what to do about it'),
                $this->discussionRow(4, 'Reached only through the private thread'),
            ],
        ]);
    }

    /**
     * A discussion found through one the reader cannot open stayed on the
     * page with no line to it, which told them something sat in between.
     */
    #[Test]
    public function a_discussion_reached_only_through_a_hidden_one_is_left_out(): void
    {
        $graph = $this->app()->getContainer()->make(ReferenceGraphQuery::class);
        $reader = User::query()->findOrFail(2);

        $drawn = $graph->visible([
            'ids' => [1, 2, 3, 4],
            'edges' => [
                ['from' => 2, 'to' => 1],
                ['from' => 3, 'to' => 1],
                ['from' => 4, 'to' => 2],
            ],
        ], $reader, 1);

        $this->assertSame([1, 3], array_column($drawn['nodes'], 'id'));
        $this->assertSame([['from' => 3, 'to' => 1]], $drawn['edges']);
    }

    #[Test]
    public function many_links_between_two_discussions_draw_one_line(): void
    {
        $this->reply(3, 'See '.$this->forum().'/d/1', 1);
        $this->reply(3, 'Again '.$this->forum().'/d/1', 1);

        $body = json_decode((string) $this->send(
            $this->request('GET', '/api/datlechin-references/graph', ['authenticatedAs' => 2])->withQueryParams(['id' => '1'])
        )->getBody(), true);

        $this->assertSame([['from' => 3, 'to' => 1]], $body['edges']);
    }
}
