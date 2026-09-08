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
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * A reference is only as visible as its two ends. Recording is deliberately
 * not gated on visibility, because a row nobody can read leaks nothing; every
 * read is.
 */
class HidesAReferenceTheReaderCannotSeeTest extends TestCase
{
    use RecordsReferences;
    use RetrievesAuthorizedUsers;

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension('datlechin-references');

        $private = $this->discussionRow(3, 'Behind a curtain');
        $private['is_private'] = true;

        $this->prepareDatabase([
            'users' => [$this->normalUser()],
            Discussion::class => [
                $this->discussionRow(1, 'Seasoning a cast iron pan'),
                $this->discussionRow(2, 'Rust, and what to do about it'),
                $private,
            ],
        ]);
    }

    protected function visibleTo(?int $userId): int
    {
        $actor = $userId === null
            ? new \Flarum\User\Guest
            : \Flarum\User\User::query()->findOrFail($userId);

        return Reference::whereVisibleTo($actor)->count();
    }

    #[Test]
    public function a_reference_between_two_readable_discussions_is_visible(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->assertSame(1, $this->visibleTo(2));
        $this->assertSame(1, $this->visibleTo(null));
    }

    #[Test]
    public function a_reference_whose_target_is_hidden_is_not_listed(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/3');

        $this->assertSame(1, Reference::query()->count());
        $this->assertSame(0, $this->visibleTo(2));
        $this->assertSame(0, $this->visibleTo(null));
    }

    #[Test]
    public function a_reference_whose_target_was_deleted_stops_being_listed(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->send($this->request('DELETE', '/api/discussions/1', ['authenticatedAs' => 1]));

        // The row survives for the broken link report, but nothing shows it to
        // a reader: no registered target can vouch for an id that is gone.
        $this->assertSame(1, Reference::query()->count());
        $this->assertSame(0, $this->visibleTo(2));
    }
}
