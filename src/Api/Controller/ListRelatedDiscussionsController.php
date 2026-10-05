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

use Datlechin\References\Service\RelatedDiscussionsQuery;
use Datlechin\References\Settings\Config;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

final class ListRelatedDiscussionsController implements RequestHandlerInterface
{
    public function __construct(
        private RelatedDiscussionsQuery $related,
        private SlugManager $slugs,
        private Cache $cache,
        private Config $config,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $id = CacheKey::id(Arr::get($request->getQueryParams(), 'id'));

        $discussion = Discussion::whereVisibleTo($actor)->find($id);

        if ($discussion === null) {
            return new EmptyResponse(404);
        }

        // Only the ranking is cached, because it is the same for everybody.
        // Which of it a reader may open is decided on every request.
        $ttl = $this->config->cacheTtl();
        $rank = fn () => $this->related->rank($id);
        $ranked = $ttl > 0 ? $this->cache->remember('datlechin-references.related.'.$id, $ttl, $rank) : $rank();

        $data = [];

        foreach ($this->related->visible($ranked, $actor) as $related) {
            $data[] = [
                'id' => (int) $related->id,
                'title' => $related->title,
                'slug' => $this->slugs->forResource(Discussion::class)->toSlug($related),
                'commentCount' => $related->comment_count,
                'referencesCount' => CacheKey::id($related->references_count),
            ];
        }

        return new JsonResponse(['data' => $data]);
    }
}
