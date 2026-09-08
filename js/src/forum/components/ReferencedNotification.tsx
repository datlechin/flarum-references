import app from 'flarum/forum/app';
import Notification from 'flarum/forum/components/Notification';

/**
 * Both blueprints record where the reference was written, so both notifications
 * lead to the same place: the post that did the referencing, not the thing it
 * pointed at, which the reader already has open.
 */
export default abstract class ReferencedNotification extends Notification {
  icon(): string {
    return 'fas fa-link';
  }

  href(): string {
    const content = this.attrs.notification.content() as { sourceDiscussionId?: number; sourcePostNumber?: number } | null;
    const near = content?.sourcePostNumber;

    if (!content?.sourceDiscussionId) return app.route('index');

    return app.route(near && near !== 1 ? 'discussion.near' : 'discussion', {
      id: content.sourceDiscussionId,
      near: near && near !== 1 ? near : undefined,
    });
  }
}
