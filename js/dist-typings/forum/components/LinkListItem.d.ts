import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type Reference from '../../common/models/Reference';
export interface ILinkListItemAttrs extends ComponentAttrs {
    reference: Reference;
    /** Name the discussion this one links to, rather than the one linking here. */
    outgoing?: boolean;
    /** Called after a moderator edits or removes the link. */
    onchange?: () => void;
}
export default class LinkListItem<CustomAttrs extends ILinkListItemAttrs = ILinkListItemAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
    private edit;
    private remove;
}
