import type Mithril from 'mithril';
import DiscussionReferencedNotification from './DiscussionReferencedNotification';
/**
 * The follower's copy of the author's notification. Same subject and the same
 * destination; only who it is addressed to differs, which is the whole reason
 * it is a type of its own.
 */
export default class FollowedDiscussionReferencedNotification extends DiscussionReferencedNotification {
    content(): Mithril.Children;
}
