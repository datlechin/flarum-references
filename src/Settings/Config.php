<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Settings;

use Flarum\Settings\SettingsRepositoryInterface;

/**
 * A setting saved through the admin page arrives as a string whatever the
 * extender declared, so every read is funnelled through here.
 */
final class Config
{
    public const PREFIX = 'datlechin-references.';

    /**
     * @var array<string, mixed>
     */
    public const DEFAULTS = [
        self::PREFIX.'enabled' => true,
        self::PREFIX.'event_post_enabled' => false,
        self::PREFIX.'extract_url_references' => true,
        self::PREFIX.'extract_short_references' => true,
        self::PREFIX.'extract_mention_references' => true,
        self::PREFIX.'max_preview' => 4,
        self::PREFIX.'related_discussions_enabled' => true,
        self::PREFIX.'related_discussions_limit' => 5,
        self::PREFIX.'related_max_candidates' => 500,
        self::PREFIX.'cache_ttl' => 300,
        self::PREFIX.'graph_max_depth' => 2,
        self::PREFIX.'graph_max_per_hop' => 50,
        self::PREFIX.'notify_followers' => true,
        self::PREFIX.'broken_retention_days' => 365,
    ];

    public function __construct(private SettingsRepositoryInterface $settings)
    {
    }

    public function enabled(): bool
    {
        return $this->boolean('enabled');
    }

    public function eventPostEnabled(): bool
    {
        return $this->boolean('event_post_enabled');
    }

    public function extractUrlReferences(): bool
    {
        return $this->boolean('extract_url_references');
    }

    public function extractShortReferences(): bool
    {
        return $this->boolean('extract_short_references');
    }

    public function extractMentionReferences(): bool
    {
        return $this->boolean('extract_mention_references');
    }

    public function notifyFollowers(): bool
    {
        return $this->boolean('notify_followers');
    }

    public function relatedDiscussionsEnabled(): bool
    {
        return $this->boolean('related_discussions_enabled');
    }

    public function maxPreview(): int
    {
        return self::previewLimit($this->settings->get(self::PREFIX.'max_preview'));
    }

    public function relatedDiscussionsLimit(): int
    {
        return $this->positive('related_discussions_limit');
    }

    public function relatedMaxCandidates(): int
    {
        return $this->positive('related_max_candidates');
    }

    public function cacheTtl(): int
    {
        return max(0, $this->number('cache_ttl'));
    }

    public function graphMaxDepth(): int
    {
        return min(4, $this->positive('graph_max_depth'));
    }

    public function graphMaxPerHop(): int
    {
        return $this->positive('graph_max_per_hop');
    }

    public function brokenRetentionDays(): int
    {
        return max(0, $this->number('broken_retention_days'));
    }

    /**
     * At least one. An administrator who clears the field is asking for a
     * shorter list, not for one nobody ever sees. The browser reads the same
     * number and applies the same floor.
     */
    public static function previewLimit(mixed $value): int
    {
        return is_numeric($value) ? max(1, (int) $value) : 4;
    }

    private function boolean(string $key): bool
    {
        $stored = $this->settings->get(self::PREFIX.$key);

        return $stored === null ? self::DEFAULTS[self::PREFIX.$key] === true : (bool) $stored;
    }

    private function number(string $key): int
    {
        $stored = $this->settings->get(self::PREFIX.$key);

        if (is_numeric($stored)) {
            return (int) $stored;
        }

        $default = self::DEFAULTS[self::PREFIX.$key] ?? null;

        return is_int($default) ? $default : 0;
    }

    private function positive(string $key): int
    {
        return max(1, $this->number($key));
    }
}
