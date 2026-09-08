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
use Flarum\Discussion\Discussion;
use Flarum\Http\RequestUtil;
use Flarum\Http\SlugManager;
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

        $data = [];

        foreach ($this->related->get($id, $actor) as $related) {
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
