<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References;

enum ReferenceOrigin: string
{
    case Url = 'url';
    case ShortRef = 'shortref';
    case Mention = 'mention';
    case Manual = 'manual';
}
