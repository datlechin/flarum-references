import app from 'flarum/forum/app';
import type Mithril from 'mithril';

import ReferencedNotification from './ReferencedNotification';

export default class DiscussionReferencedNotification extends ReferencedNotification {
  content(): Mithril.Children {
    return app.translator.trans('datlechin-references.forum.notifications.discussion_referenced_text', {
      username: this.attrs.notification.fromUser(),
    });
  }

  excerpt(): Mithril.Children {
    const subject = this.attrs.notification.subject();

    return subject && 'title' in subject ? (subject as { title(): string }).title() : null;
  }
}
