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
use Illuminate\Database\Eloquent\Collection;
use Psr\Http\Message\ResponseInterface;

trait RecordsReferences
{
    protected function forum(): string
    {
        return 'http://localhost';
    }

    /**
     * @return array<string, mixed>
     */
    protected function discussionRow(int $id, string $title, int $userId = 2): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'slug' => 'discussion-'.$id,
            'created_at' => Carbon::parse('2026-01-01 00:00:00')->toDateTimeString(),
            'user_id' => $userId,
            'comment_count' => 1,
            'is_private' => false,
        ];
    }

    /**
     * Posts are written through the API, because that is the only path that
     * releases the events this extension listens to. A direct save would
     * queue them and never dispatch, which is exactly the case the backfill
     * command exists for.
     */
    protected function reply(int $discussionId, string $content, int $authenticatedAs = 2): ResponseInterface
    {
        return $this->send(
            $this->request('POST', '/api/posts', [
                'authenticatedAs' => $authenticatedAs,
                'json' => [
                    'data' => [
                        'attributes' => ['content' => $content],
                        'relationships' => [
                            'discussion' => ['data' => ['type' => 'discussions', 'id' => (string) $discussionId]],
                        ],
                    ],
                ],
            ])
        );
    }

    protected function edit(int $postId, string $content, int $authenticatedAs = 2): ResponseInterface
    {
        return $this->send(
            $this->request('PATCH', '/api/posts/'.$postId, [
                'authenticatedAs' => $authenticatedAs,
                'json' => ['data' => ['attributes' => ['content' => $content]]],
            ])
        );
    }

    /**
     * @return Collection<int, Reference>
     */
    protected function references(): Collection
    {
        /** @var Collection<int, Reference> */
        return Reference::query()->orderBy('id')->get();
    }

    protected function referencesCount(int $discussionId): int
    {
        $discussion = Discussion::query()->find($discussionId);

        return $discussion === null ? 0 : (int) $discussion->references_count;
    }
}
