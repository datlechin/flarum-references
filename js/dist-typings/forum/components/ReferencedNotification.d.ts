import Notification from 'flarum/forum/components/Notification';
/**
 * Both blueprints record where the reference was written, so both notifications
 * lead to the same place: the post that did the referencing, not the thing it
 * pointed at, which the reader already has open.
 */
export default abstract class ReferencedNotification extends Notification {
    icon(): string;
    href(): string;
}
