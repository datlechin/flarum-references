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

use Datlechin\References\Post\ReferencedEventPost;
use Flarum\Api\Endpoint;
use Flarum\Api\Resource;
use Flarum\Api\Schema;
use Flarum\Api\Sort;
use Flarum\Approval\Event\PostWasApproved;
use Flarum\Discussion\Discussion;
use Flarum\Discussion\Event\Deleting as DiscussionDeleting;
use Flarum\Discussion\Search\DiscussionSearcher;
use Flarum\Extend;
use Flarum\Post\Event\Deleted;
use Flarum\Post\Event\Deleting;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
use Flarum\Post\Post;
use Flarum\Search\Database\DatabaseSearchDriver;
use Illuminate\Database\Eloquent\Relations\HasMany;

$settings = new Extend\Settings;

foreach (Settings\Config::DEFAULTS as $key => $value) {
    $settings->default($key, $value);
}

$settings
    ->serializeToForum('datlechin-references.eventPostEnabled', Settings\Config::PREFIX.'event_post_enabled', 'boolval')
    ->serializeToForum('datlechin-references.maxPreview', Settings\Config::PREFIX.'max_preview', Settings\Config::previewLimit(...))
    ->serializeToForum('datlechin-references.relatedDiscussionsEnabled', Settings\Config::PREFIX.'related_discussions_enabled', 'boolval')
    ->serializeToForum('datlechin-references.shortReferencesEnabled', Settings\Config::PREFIX.'extract_short_references', 'boolval');

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__.'/js/dist/forum.js')
        ->css(__DIR__.'/less/forum.less'),

    (new Extend\Frontend('admin'))
        ->js(__DIR__.'/js/dist/admin.js')
        ->css(__DIR__.'/less/admin.less'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\View)
        ->namespace('datlechin-references', __DIR__.'/views'),

    (new Extend\ServiceProvider())
        ->register(ReferencesServiceProvider::class),

    (new Extend\Model(Post::class))
        // Not `morphMany`: it constrains `target_type` by `getMorphClass()`,
        // and a real post is a subclass such as CommentPost, whose morph class
        // is not the `posts` alias the rows were written with. The relation
        // silently matched nothing, so a referenced post never showed its
        // backlinks.
        ->relationship('referencedBy', fn (Post $post): HasMany => $post
            ->hasMany(Reference::class, 'target_id')
            ->where('target_type', Reference::TARGET_POST))
        ->hasMany('outgoingReferences', Reference::class, 'source_post_id'),

    (new Extend\Model(Discussion::class))
        // Everything pointing anywhere inside the discussion, itself and every
        // post in it, which is why this hangs off the denormalised column.
        ->hasMany('referencedBy', Reference::class, 'target_discussion_id')
        ->hasMany('outgoingReferences', Reference::class, 'source_discussion_id'),

    (new Extend\ModelVisibility(Reference::class))
        ->scope(Access\ScopeReferenceVisibility::class),

    new Extend\ApiResource(Api\Resource\ReferenceResource::class),

    // Sent rather than hand-copied into the frontend, so the picker cannot
    // drift from what the column will accept.
    (new Extend\ApiResource(Resource\ForumResource::class))
        ->fields(fn () => [
            Schema\Arr::make('datlechin-references.relationTypes')
                ->get(fn () => RelationType::values()),
        ]),

    (new Extend\ApiResource(Resource\PostResource::class))
        ->fields(Api\PostResourceFields::class)
        ->endpoint([Endpoint\Index::class, Endpoint\Show::class], fn (Endpoint\Index|Endpoint\Show $endpoint) => $endpoint
            ->addDefaultInclude(['referencedBy', 'referencedBy.sourcePost', 'referencedBy.sourceDiscussion'])),

    (new Extend\ApiResource(Resource\DiscussionResource::class))
        ->fields(Api\DiscussionResourceFields::class)
        // The ranking column, offered where a reader already reorders the list.
        ->sorts(fn () => [Sort\SortColumn::make('referencesCount')])
        // Both directions, because the sidebar draws both. Without the outgoing
        // side the count arrived but the rows did not, and the section that
        // lists what this discussion points at never appeared.
        //
        // The source post matters as much as the discussion: a row without it
        // links to the top of the citing discussion instead of to the citing
        // post, and names that discussion's starter rather than whoever wrote
        // the citation. `sourceDiscussion.user` covers the manual reference,
        // where there is legitimately no source post to name.
        //
        // The first and last post are shipped by core as full post resources of
        // their own, and an unincluded relationship carries no rows, so without
        // the last two lines those two posts arrive with a backlink count and
        // nothing to draw, and their footer silently disappears.
        ->endpoint(Endpoint\Show::class, fn (Endpoint\Show $endpoint) => $endpoint
            ->addDefaultInclude([
                'referencedBy',
                'referencedBy.sourcePost',
                'referencedBy.sourcePost.user',
                'referencedBy.sourceDiscussion',
                'referencedBy.sourceDiscussion.user',
                'outgoingReferences',
                'outgoingReferences.targetDiscussion',
                'firstPost.referencedBy',
                'firstPost.referencedBy.sourcePost',
                'firstPost.referencedBy.sourceDiscussion',
                'lastPost.referencedBy',
                'lastPost.referencedBy.sourcePost',
                'lastPost.referencedBy.sourceDiscussion',
            ])),

    (new Extend\Formatter)
        ->configure(Formatter\ConfigureDiscussionReferences::class)
        ->render(Formatter\FormatDiscussionReferences::class)
        ->unparse(Formatter\UnparseDiscussionReferences::class),

    (new Extend\Event())
        ->listen(Posted::class, Listener\SyncReferences::class)
        ->listen(Revised::class, Listener\SyncReferences::class)
        ->listen(Restored::class, Listener\SyncReferences::class)
        ->listen(PostWasApproved::class, Listener\SyncReferences::class)
        ->listen(Hidden::class, Listener\ClearReferences::class)
        // Both: the rows have to be counted and their notifications retracted
        // while they still exist, and what pointed at the post can only be
        // marked broken once the delete has actually happened.
        ->listen(Deleting::class, Listener\ClearReferences::class)
        ->listen(Deleted::class, Listener\ClearReferences::class)
        // Deleting a discussion tears its posts down with a database cascade
        // that fires no per post event, so every concern for that path lives
        // in one listener rather than the ones above.
        ->listen(DiscussionDeleting::class, Listener\CleanUpOnDiscussionDeleting::class),

    (new Extend\Notification())
        ->type(Notification\DiscussionReferencedBlueprint::class, ['alert'])
        ->type(Notification\PostReferencedBlueprint::class, ['alert']),

    (new Extend\Post)
        ->type(ReferencedEventPost::class),

    (new Extend\SearchDriver(DatabaseSearchDriver::class))
        ->addSearcher(Reference::class, Search\ReferenceSearcher::class)
        ->addFilter(Search\ReferenceSearcher::class, Search\Filter\TargetFilter::class)
        ->addFilter(Search\ReferenceSearcher::class, Search\Filter\TargetDiscussionFilter::class)
        ->addFilter(Search\ReferenceSearcher::class, Search\Filter\SourceDiscussionFilter::class)
        ->addFilter(DiscussionSearcher::class, Search\Filter\ReferencesFilter::class)
        ->addFilter(DiscussionSearcher::class, Search\Filter\ReferencedByFilter::class)
        ->addFilter(DiscussionSearcher::class, Search\Filter\HasReferencesFilter::class),

    (new Extend\Routes('api'))
        ->get('/datlechin-references/related', 'datlechin-references.related', Api\Controller\ListRelatedDiscussionsController::class)
        ->get('/datlechin-references/graph', 'datlechin-references.graph', Api\Controller\ShowReferenceGraphController::class)
        ->get('/datlechin-references/broken', 'datlechin-references.broken', Api\Controller\ListBrokenReferencesController::class)
        ->get('/datlechin-references/stats', 'datlechin-references.stats', Api\Controller\ListReferenceStatsController::class)
        ->get('/datlechin-references/stats/export', 'datlechin-references.stats.export', Api\Controller\ExportReferenceStatsController::class),

    (new Extend\Console())
        ->command(Console\BackfillCommand::class)
        ->command(Console\ReconcileCountersCommand::class)
        ->command(Console\BuildDailyRollupCommand::class)
        ->command(Console\PurgeBrokenReferencesCommand::class)
        ->schedule(Console\ReconcileCountersCommand::class, Console\DailySchedule::class)
        ->schedule(Console\BuildDailyRollupCommand::class, Console\DailySchedule::class)
        ->schedule(Console\PurgeBrokenReferencesCommand::class, Console\DailySchedule::class),

    (new Extend\Conditional())
        ->whenExtensionEnabled('flarum-gdpr', fn () => [
            (new \Flarum\Gdpr\Extend\UserData())
                ->addType(Gdpr\References::class),
        ]),

    $settings,
];
