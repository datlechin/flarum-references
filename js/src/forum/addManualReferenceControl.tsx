import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import DiscussionControls from 'flarum/forum/utils/DiscussionControls';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import ManualReferenceModal from './components/ManualReferenceModal';

export default function addManualReferenceControl() {
  extend(DiscussionControls, 'moderationControls', function (items: ItemList<Mithril.Children>, discussion: Discussion) {
    if (!discussion.attribute<boolean>('canManageReferences')) return;

    items.add(
      'addReference',
      <Button icon="fas fa-link" onclick={() => app.modal.show(ManualReferenceModal, { discussion })}>
        {app.translator.trans('datlechin-references.forum.manual.add_button')}
      </Button>
    );
  });
}
