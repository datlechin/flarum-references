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

use Datlechin\References\Service\ReferenceStatsQuery;
use Flarum\Http\RequestUtil;
use Laminas\Diactoros\Response\TextResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;

final class ExportReferenceStatsController implements RequestHandlerInterface
{
    public function __construct(
        private ReferenceStatsQuery $stats,
    ) {
    }

    public function handle(Request $request): ResponseInterface
    {
        RequestUtil::getActor($request)->assertCan('datlechin-references.viewAnalytics');

        [$from, $to] = ListReferenceStatsController::range($request->getQueryParams());

        $handle = fopen('php://temp', 'r+');

        if ($handle === false) {
            return new TextResponse('', 500);
        }

        fputcsv($handle, ['date', 'references']);

        foreach ($this->stats->daily($from, $to) as $row) {
            fputcsv($handle, [$row['date'], $row['count']]);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return new TextResponse($csv, 200, [
            'content-type' => 'text/csv; charset=utf-8',
            'content-disposition' => 'attachment; filename="references-'.$from->toDateString().'-to-'.$to->toDateString().'.csv"',
        ]);
    }
}
