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

use Carbon\Carbon;
use Datlechin\References\Service\ReferenceStatsQuery;
use Flarum\Http\RequestUtil;
use Illuminate\Support\Arr;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

final class ListReferenceStatsController implements RequestHandlerInterface
{
    public function __construct(
        private ReferenceStatsQuery $stats,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertCan('datlechin-references.viewAnalytics');

        [$from, $to] = self::range($request->getQueryParams());

        return new JsonResponse([
            'data' => [
                'daily' => $this->stats->daily($from, $to),
                'byRelationType' => $this->stats->byRelationType(),
                'byOrigin' => $this->stats->byOrigin(),
                'totals' => $this->stats->totals(),
            ],
        ]);
    }

    /**
     * @param array<string, mixed> $params
     * @return array{Carbon, Carbon}
     */
    public static function range(array $params): array
    {
        $to = self::date(Arr::get($params, 'to')) ?? Carbon::today();
        $from = self::date(Arr::get($params, 'from')) ?? $to->copy()->subDays(29);

        return $from->lessThanOrEqualTo($to) ? [$from, $to] : [$to, $from];
    }

    private static function date(mixed $value): ?Carbon
    {
        if (! is_string($value) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1) {
            return null;
        }

        return Carbon::createFromFormat('Y-m-d', $value)?->startOfDay();
    }
}
