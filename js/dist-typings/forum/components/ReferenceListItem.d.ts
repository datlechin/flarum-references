import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import type Reference from '../../common/models/Reference';
export interface IReferenceListItemAttrs extends ComponentAttrs {
    reference: Reference;
    /** Name the far end rather than the near one, for a list of what this discussion cites. */
    outgoing?: boolean;
    onclick?: () => void;
}
export default class ReferenceListItem<CustomAttrs extends IReferenceListItemAttrs = IReferenceListItemAttrs> extends Component<CustomAttrs> {
    view(): Mithril.Children;
}
