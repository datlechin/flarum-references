import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import type ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

/**
 * A type the grid does not list has no row in the notification settings, so
 * nobody could turn these off or ask for them by email.
 *
 * Extended by module path, not by import: the grid is a lazily loaded chunk in
 * Flarum 2, so an imported `NotificationGrid` is undefined when the
 * initializer runs. Reading its prototype threw, and every step of the
 * initializer after this one, the `#` discussion picker among them, silently
 * never ran.
 */
export default function addNotificationPreferences() {
  extend(
    'flarum/forum/components/NotificationGrid',
    'notificationTypes',
    function (items: ItemList<{ name: string; icon: string; label: Mithril.Children }>) {
      items.add('discussionReferenced', {
        name: 'discussionReferenced',
        icon: 'fas fa-link',
        label: app.translator.trans('datlechin-references.forum.settings.notify_discussion_linked_label'),
      });

      items.add('postReferenced', {
        name: 'postReferenced',
        icon: 'fas fa-link',
        label: app.translator.trans('datlechin-references.forum.settings.notify_post_linked_label'),
      });

      items.add('followedDiscussionReferenced', {
        name: 'followedDiscussionReferenced',
        icon: 'fas fa-link',
        label: app.translator.trans('datlechin-references.forum.settings.notify_followed_discussion_linked_label'),
      });
    }
  );
}
