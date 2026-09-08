<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Notification;

use Flarum\Database\AbstractModel;
use Flarum\Discussion\Discussion;
use Flarum\Locale\TranslatorInterface;
use Flarum\Notification\AlertableInterface;
use Flarum\Notification\Blueprint\BlueprintInterface;
use Flarum\Notification\MailableInterface;
use Flarum\Post\Post;
use Flarum\User\User;

final class DiscussionReferencedBlueprint implements BlueprintInterface, AlertableInterface, MailableInterface
{
    public function __construct(
        public Discussion $discussion,
        public Post $source,
    ) {
    }

    public function getFromUser(): ?User
    {
        return $this->source->user;
    }

    public function getSubject(): AbstractModel
    {
        return $this->discussion;
    }

    public function getData(): array
    {
        return [
            'sourcePostId' => $this->source->id,
            'sourceDiscussionId' => $this->source->discussion_id,
            'sourcePostNumber' => $this->source->number,
        ];
    }

    public function getEmailViews(): array
    {
        return [
            'text' => 'datlechin-references::emails.plain.discussionReferenced',
            'html' => 'datlechin-references::emails.html.discussionReferenced',
        ];
    }

    public function getEmailSubject(TranslatorInterface $translator): string
    {
        return $translator->trans('datlechin-references.email.discussion_referenced.subject', [
            '{title}' => $this->discussion->title,
        ]);
    }

    public static function getType(): string
    {
        return 'discussionReferenced';
    }

    public static function getSubjectModel(): string
    {
        return Discussion::class;
    }
}
