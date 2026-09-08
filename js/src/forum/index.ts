import app from 'flarum/forum/app';

import addBacklinkFooterItem from './addBacklinkFooterItem';
import addDiscussionSidebar from './addDiscussionSidebar';
import addMostReferencedSort from './addMostReferencedSort';
import addManualReferenceControl from './addManualReferenceControl';
import registerMentionable from './registerMentionable';
import { filterDiscussionReferences, postFilterDiscussionReferences } from './utils/textFormatter';

export { default as extend } from './extend';

// Read by the tag's parse-time filter chain through setJS(), so the composer
// preview resolves a reference the same way the server will.
export { filterDiscussionReferences, postFilterDiscussionReferences };

app.initializers.add('datlechin-references', () => {
  addBacklinkFooterItem();
  addDiscussionSidebar();
  addMostReferencedSort();
  addManualReferenceControl();
  registerMentionable();
});
