import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
import ReferenceListState from '../states/ReferenceListState';
export interface IReferenceListModalAttrs extends IInternalModalAttrs {
    filter: {
        target?: string;
        targetDiscussion?: string;
        sourceDiscussion?: string;
    };
    modalTitle: Mithril.Children;
}
export default class ReferenceListModal<CustomAttrs extends IReferenceListModalAttrs = IReferenceListModalAttrs> extends Modal<CustomAttrs> {
    protected list: ReferenceListState;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
}
