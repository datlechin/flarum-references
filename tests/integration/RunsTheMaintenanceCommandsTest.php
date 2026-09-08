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

use Carbon\Carbon;
use Datlechin\References\Reference;
use Flarum\Discussion\Discussion;
use Flarum\Testing\integration\ConsoleTestCase;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use PHPUnit\Framework\Attributes\Test;

/**
 * These four carry the only hand written SQL in the extension, including the
 * aggregates whose result column is named differently per driver. Running them
 * is the cheapest way to keep MySQL, MariaDB, PostgreSQL and SQLite honest.
 */
class RunsTheMaintenanceCommandsTest extends ConsoleTestCase
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

    #[Test]
    public function the_backfill_records_what_the_listeners_missed(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        Reference::query()->delete();
        $this->assertCount(0, $this->references());

        $output = $this->runCommand(['command' => 'references:backfill']);

        $this->assertStringContainsString('recorded 1 references', $output);
        $this->assertCount(1, $this->references());
    }

    #[Test]
    public function the_backfill_can_be_run_twice_without_doubling_anything(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->runCommand(['command' => 'references:backfill']);
        $this->runCommand(['command' => 'references:backfill']);

        $this->assertCount(1, $this->references());
    }

    #[Test]
    public function reconcile_repairs_a_counter_that_drifted(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $this->database()->table('discussions')->where('id', 1)->update(['references_count' => 99]);

        $output = $this->runCommand(['command' => 'references:reconcile']);

        $this->assertStringContainsString('Corrected 1 discussions.', $output);
        $this->assertSame(1, $this->referencesCount(1));
    }

    #[Test]
    public function reconcile_leaves_a_correct_counter_alone(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $output = $this->runCommand(['command' => 'references:reconcile']);

        $this->assertStringContainsString('Corrected 0 discussions.', $output);
    }

    /**
     * The rollup only ever covers whole days that have closed, so the row has
     * to be dated into the past before there is anything for it to count.
     */
    #[Test]
    public function the_rollup_counts_a_closed_day(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        $yesterday = Carbon::yesterday();

        Reference::query()->update(['created_at' => $yesterday->copy()->setTime(12, 0)]);

        $this->runCommand(['command' => 'references:build-daily-rollup', '--rebuild' => true]);

        $row = $this->database()->table('reference_daily')->where('date', $yesterday->toDateString())->first();

        $this->assertNotNull($row, 'the rollup wrote no row for yesterday');
        $this->assertSame(1, (int) $row->total);
    }

    #[Test]
    public function the_purge_drops_only_what_is_past_retention(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        Reference::query()->update(['target_deleted_at' => Carbon::now()->subDays(400)]);

        $output = $this->runCommand(['command' => 'references:purge-broken']);

        $this->assertStringContainsString('Purged 1 broken references.', $output);
        $this->assertCount(0, $this->references());
    }

    #[Test]
    public function the_purge_keeps_a_reference_that_is_still_within_retention(): void
    {
        $this->reply(2, 'See '.$this->forum().'/d/1');

        Reference::query()->update(['target_deleted_at' => Carbon::now()->subDays(2)]);

        $this->runCommand(['command' => 'references:purge-broken']);

        $this->assertCount(1, $this->references());
    }
}
