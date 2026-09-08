<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Extraction;

use Flarum\Http\UrlGenerator;

/**
 * Turns an address written in a post into a path this forum owns.
 *
 * Core answers the same question in `Formatter::isInternalUrl()` and
 * `parseDiscussionUrl()`, and again in `labelDiscussionLinks.ts`, but both are
 * protected and the stored XML never carries what they work out at render
 * time. Their rules are mirrored here on purpose, so a link core labels and a
 * link we record are always the same set.
 */
final class ForumUrl
{
    /**
     * @var array{host: string, port: int, path: string}|null
     */
    private ?array $origin = null;

    public function __construct(
        private UrlGenerator $url,
    ) {
    }

    public function path(string $url): ?string
    {
        if (preg_match('~^[a-z][a-z0-9+.-]*:~i', $url) === 1 && preg_match('~^https?://~i', $url) !== 1) {
            return null;
        }

        if (str_starts_with($url, '//')) {
            return null;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return null;
        }

        $origin = $this->origin();

        if (isset($parts['host'])) {
            if (self::host($parts['host']) !== $origin['host']) {
                return null;
            }

            $port = $parts['port'] ?? null;

            if ($port !== null && $port !== $origin['port']) {
                return null;
            }
        }

        $path = is_string($parts['path'] ?? null) ? $parts['path'] : '';

        if ($origin['path'] !== '') {
            if ($path !== $origin['path'] && ! str_starts_with($path, $origin['path'].'/')) {
                return null;
            }

            $path = substr($path, strlen($origin['path']));
        }

        return rtrim($path, '/');
    }

    /**
     * @return array{host: string, port: int, path: string}
     */
    protected function origin(): array
    {
        if ($this->origin !== null) {
            return $this->origin;
        }

        $base = parse_url($this->url->to('forum')->base()) ?: [];
        $scheme = strtolower(is_string($base['scheme'] ?? null) ? $base['scheme'] : 'https');
        $port = $base['port'] ?? null;

        return $this->origin = [
            'host' => self::host(is_string($base['host'] ?? null) ? $base['host'] : ''),
            'port' => is_int($port) ? $port : ($scheme === 'https' ? 443 : 80),
            'path' => rtrim(is_string($base['path'] ?? null) ? $base['path'] : '', '/'),
        ];
    }

    protected static function host(string $host): string
    {
        return trim(strtolower(rtrim(trim($host), '.')), '[]');
    }
}
