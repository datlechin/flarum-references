import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import LinksModal from './components/LinksModal';

/**
 * "Manage links" in the discussion's moderation menu, beside Rename and Lock.
 * It opens the same list readers see, with the moderator's actions on it.
 */
export default function addManageLinksControl() {
  extend(DiscussionControls, 'moderationControls', function (items: ItemList<Mithril.Children>, discussion: Discussion) {
    if (!discussion.attribute<boolean>('canManageReferences')) return;

    items.add(
      'manageLinks',
      <Button icon="fas fa-link" onclick={() => app.modal.show(LinksModal, { discussion })}>
        {app.translator.trans('datlechin-references.forum.links.manage_button')}
      </Button>
    );
  });
}
