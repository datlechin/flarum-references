<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Tests\unit;

use Datlechin\References\RelationType;
use Datlechin\References\Settings\Config;
use Flarum\Testing\unit\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;

/**
 * An extension's locale file is not loaded in the integration test app, so
 * asserting a translated string there proves nothing. The file is read
 * directly instead.
 */
class NamesAStringForEveryRelationTest extends TestCase
{
    /**
     * @var array<string, mixed>
     */
    private array $locale;

    protected function setUp(): void
    {
        parent::setUp();

        $parsed = Yaml::parseFile(__DIR__.'/../../locale/en.yml');

        $this->locale = is_array($parsed) ? $parsed : [];
    }

    #[Test]
    public function every_relation_type_can_be_read_aloud(): void
    {
        foreach (RelationType::cases() as $relation) {
            $this->assertIsString(
                $this->string('forum.relation.'.$relation->value),
                'Missing a name for the '.$relation->value.' relation.'
            );
        }
    }

    /**
     * The settings page passes both to every field, so a missing one is not a
     * blank space, it is the raw key printed on the page.
     */
    #[Test]
    public function every_setting_has_a_label_and_a_help_string(): void
    {
        foreach (array_keys(Config::DEFAULTS) as $key) {
            $name = substr($key, strlen(Config::PREFIX));

            foreach (['_label', '_help'] as $suffix) {
                $this->assertIsString(
                    $this->string('admin.settings.'.$name.$suffix),
                    'Missing '.$suffix.' for the '.$name.' setting.'
                );
            }
        }
    }

    private function string(string $path): mixed
    {
        $node = $this->locale['datlechin-references'] ?? [];

        foreach (explode('.', $path) as $segment) {
            if (! is_array($node) || ! array_key_exists($segment, $node)) {
                return null;
            }

            $node = $node[$segment];
        }

        return $node;
    }
}
