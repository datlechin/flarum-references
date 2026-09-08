<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Job;

use Datlechin\References\Service\ReferenceNotifier;
use Flarum\Queue\AbstractJob;

final class SendReferenceNotifications extends AbstractJob
{
    /**
     * @param list<int> $referenceIds
     */
    public function __construct(
        private array $referenceIds,
    ) {
        parent::__construct();
    }

    public function handle(ReferenceNotifier $notifier): void
    {
        $notifier->notify($this->referenceIds);
    }
}
