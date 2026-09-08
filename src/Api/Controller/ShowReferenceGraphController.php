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

use Datlechin\References\Service\ReferenceGraphQuery;
use Datlechin\References\Settings\Config;
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

final class ShowReferenceGraphController implements RequestHandlerInterface
{
    public function __construct(
        private ReferenceGraphQuery $graph,
        private Cache $cache,
        private Config $config,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        $actor = RequestUtil::getActor($request);
        $id = CacheKey::id(Arr::get($request->getQueryParams(), 'id'));

        if (Discussion::whereVisibleTo($actor)->find($id) === null) {
            return new EmptyResponse(404);
        }

        $ttl = $this->config->cacheTtl();
        $key = 'datlechin-references.graph.'.$id;

        // Only the walk is cached, and it is the same for everybody. Titles are
        // fetched per request, because two readers in the same groups do not
        // necessarily see the same discussions and the old per-group key served
        // one reader's titles to the other.
        $walk = fn () => $this->graph->traverse($id);

        return new JsonResponse($this->graph->visible(
            $ttl > 0 ? $this->cache->remember($key, $ttl, $walk) : $walk(),
            $actor
        ));
    }
}
