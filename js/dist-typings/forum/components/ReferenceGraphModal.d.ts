import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';
export interface IReferenceGraphModalAttrs extends IInternalModalAttrs {
    discussionId: string;
}
export default class ReferenceGraphModal<CustomAttrs extends IReferenceGraphModalAttrs = IReferenceGraphModalAttrs> extends Modal<CustomAttrs> {
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
}
