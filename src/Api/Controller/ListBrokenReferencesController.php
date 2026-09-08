<?php

/*
 * This file is part of datlechin/flarum-references.
 *
 * Copyright (c) 2026 Ngo Quoc Dat.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Datlechin\References\Api\Controller;

use Datlechin\References\Reference;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Deliberately unscoped by reference visibility: a broken row's target is gone,
 * so no visibility rule can speak for it. The permission is the gate.
 */
final class ListBrokenReferencesController implements RequestHandlerInterface
{
    private const PER_PAGE = 50;

    public function __construct(
        private SlugManager $slugs,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $actor->assertCan('datlechin-references.viewBrokenReport');

        $params = $request->getQueryParams();
        $offset = max(0, CacheKey::id(Arr::get($params, 'offset')));

        $query = Reference::query()
            ->whereNotNull('target_deleted_at')
            ->with('sourceDiscussion', 'sourcePost')
            // Every row a single marking pass touches carries the same
            // second, so the timestamp alone leaves ties in an order the
            // engine is free to change between one page and the next.
            ->orderByDesc('target_deleted_at')
            ->orderByDesc('id');

        $total = (clone $query)->count();

        $data = $query->offset($offset)->limit(self::PER_PAGE)->get()->map(function (Reference $reference) {
            $discussion = $reference->sourceDiscussion;

            return [
                'id' => (int) $reference->id,
                'targetType' => $reference->target_type,
                'targetId' => (int) $reference->target_id,
                'brokenAt' => $reference->target_deleted_at?->toAtomString(),
                'sourcePostNumber' => $reference->sourcePost?->number,
                'sourceDiscussion' => $discussion instanceof Discussion ? [
                    'id' => (int) $discussion->id,
                    'title' => $discussion->title,
                    'slug' => $this->slugs->forResource(Discussion::class)->toSlug($discussion),
                ] : null,
            ];
        })->values();

        return new JsonResponse([
            'data' => $data,
            'meta' => ['total' => $total, 'offset' => $offset, 'perPage' => self::PER_PAGE],
        ]);
    }
}
