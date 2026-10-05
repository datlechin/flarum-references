import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
export interface IDiscussionLinksAttrs extends ComponentAttrs {
    discussion: Discussion;
}
/**
 * The sidebar block: discussions that link here, and discussions this one
 * links to. Titles only. Dates, authors, notes, the map and moderation are in
 * LinksModal, so the sidebar stays short.
 *
 * On a phone the sidebar is a row of buttons above the first post, so the
 * block shrinks to one button there.
 */
export default class DiscussionLinks<CustomAttrs extends IDiscussionLinksAttrs = IDiscussionLinksAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
    private section;
    /**
     * One row per other discussion, already grouped by the server. Rows whose
     * other end is missing, or is this discussion, are skipped in case an old
     * payload is still cached.
     */
    private rows;
    private open;
}
