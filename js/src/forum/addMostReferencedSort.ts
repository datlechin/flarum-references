import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';
import type { SortMap } from 'flarum/common/states/PaginatedListState';

/**
 * A sort, not a sidebar widget.
 *
 * The index sidebar has no labelled group: it is a flat list of links, so four
 * bare titles under a separator say nothing about what they are, and on a small
 * forum they repeat the discussion list beside them. The sort dropdown is where
 * a reader already goes to reorder that list, it is labelled by core, and it
 * works the same on a phone, a tablet and a desktop.
 */
export default function addMostReferencedSort() {
  extend(DiscussionListState.prototype, 'sortMap', function (map: SortMap) {
    map.mostReferenced = {
      sort: '-referencesCount',
      label: app.translator.trans('datlechin-references.forum.most_referenced.sort'),
    };
  });
}
