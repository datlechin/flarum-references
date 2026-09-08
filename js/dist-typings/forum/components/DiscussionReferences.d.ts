import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
export interface IDiscussionReferencesAttrs extends ComponentAttrs {
    discussion: Discussion;
}
export default class DiscussionReferences<CustomAttrs extends IDiscussionReferencesAttrs = IDiscussionReferencesAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
    /**
     * One row per discussion, not per reference. Two posts in the same discussion
     * both carry that discussion's title, so ungrouped they render as the same
     * row twice and read as a bug.
     */
    private loaded;
    /**
     * The two directions read the same way to a reader, so they share a list.
     * What differs is which end of the row to name, which is why the item is
     * told which direction it is being shown in.
     */
    private list;
}
