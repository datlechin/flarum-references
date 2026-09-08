import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionPage from 'flarum/forum/components/DiscussionPage';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import DiscussionReferences from './components/DiscussionReferences';
import RelatedDiscussions from './components/RelatedDiscussions';

export default function addDiscussionSidebar() {
  extend(DiscussionPage.prototype, 'sidebarItems', function (items: ItemList<Mithril.Children>) {
    const discussion = this.discussion;

    if (!discussion) return;

    items.add('references', <DiscussionReferences discussion={discussion} />, -10);

    if (app.forum.attribute<boolean>('datlechin-references.relatedDiscussionsEnabled')) {
      items.add('relatedDiscussions', <RelatedDiscussions discussionId={String(discussion.id())} />, -20);
    }
  });
}
