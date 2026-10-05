import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';
import ReferenceListState from '../states/ReferenceListState';
export type LinksTab = 'incoming' | 'outgoing' | 'map';
export interface ILinksModalAttrs extends IInternalModalAttrs {
    /** Show the links of a discussion, with tabs. */
    discussion?: Discussion;
    /** Or the discussions that link to one post. */
    post?: Post;
    tab?: LinksTab;
}
/**
 * Every link of a discussion, or of a post, with details. It is also the one
 * place a moderator adds, edits and removes links.
 */
export default class LinksModal<CustomAttrs extends ILinksModalAttrs = ILinksModalAttrs> extends Modal<CustomAttrs> {
    protected tab: LinksTab;
    protected lists: Partial<Record<LinksTab, ReferenceListState>>;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    private toolbar;
    private rows;
    /**
     * Created on first view of a tab and kept, so switching back does not
     * reload it.
     */
    private list;
    private filter;
    private add;
    /**
     * Lists and counts are worked out per reader on the server, so after a
     * change both are asked for again.
     */
    private changed;
}
