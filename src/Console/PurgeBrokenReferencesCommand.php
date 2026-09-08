<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Console;

use Carbon\Carbon;
use Datlechin\References\Reference;
use Datlechin\References\Settings\Config;
use Illuminate\Console\Command;

final class PurgeBrokenReferencesCommand extends Command
{
    protected $signature = 'references:purge-broken';

    protected $description = 'Remove long broken references that nobody is going to fix.';

    public function __construct(
        private Config $config,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = $this->config->brokenRetentionDays();

        if ($days === 0) {
            $this->info('Retention is off, nothing purged.');

            return self::SUCCESS;
        }

        $deleted = Reference::query()
            ->whereNotNull('target_deleted_at')
            ->where('target_deleted_at', '<', Carbon::now()->subDays($days))
            ->delete();

        $this->info(sprintf('Purged %d broken references.', is_numeric($deleted) ? (int) $deleted : 0));

        return self::SUCCESS;
    }
}
