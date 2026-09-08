import app from 'flarum/forum/app';
import { truncate } from 'flarum/common/utils/string';
import type Mithril from 'mithril';

import ReferencedNotification from './ReferencedNotification';

export default class PostReferencedNotification extends ReferencedNotification {
  content(): Mithril.Children {
    return app.translator.trans('datlechin-references.forum.notifications.post_referenced_text', {
      username: this.attrs.notification.fromUser(),
    });
  }

  excerpt(): Mithril.Children {
    const subject = this.attrs.notification.subject();
    const plain = subject && 'contentPlain' in subject ? (subject as { contentPlain(): string | null }).contentPlain() : null;

    return truncate(plain || '', 200);
  }
}
