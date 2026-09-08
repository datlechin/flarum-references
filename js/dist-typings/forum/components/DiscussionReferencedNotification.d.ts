import type Mithril from 'mithril';
import ReferencedNotification from './ReferencedNotification';
export default class DiscussionReferencedNotification extends ReferencedNotification {
    content(): Mithril.Children;
    excerpt(): Mithril.Children;
}
