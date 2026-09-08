<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Gdpr;

use Datlechin\References\Reference;
use Flarum\Gdpr\Data\Type;

final class References extends Type
{
    public static function dataType(): string
    {
        return 'References';
    }

    public static function exportDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-references.gdpr.export_description');
    }

    public static function anonymizeDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-references.gdpr.anonymize_description');
    }

    public static function deleteDescription(): string
    {
        return self::staticTranslator()->trans('datlechin-references.gdpr.delete_description');
    }

    /**
     * @return list<string>
     */
    public static function piiFields(): array
    {
        return ['created_by_id'];
    }

    public function export(): ?array
    {
        $rows = Reference::query()
            ->where('created_by_id', $this->user->id)
            ->orderBy('created_at')
            ->get()
            ->toArray();

        if ($rows === []) {
            return null;
        }

        return [['references/manual.json' => $this->encodeForExport($rows)]];
    }

    public function anonymize(): void
    {
        Reference::query()
            ->where('created_by_id', $this->user->id)
            ->update(['created_by_id' => null]);
    }

    public function delete(): void
    {
        // The rows written by this member's own posts go with those posts.
        // What is left is the claims they made as a moderator about other
        // people's content, which are not theirs to take away.
        $this->anonymize();
    }
}
