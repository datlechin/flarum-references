import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
export interface IManualReferenceModalAttrs extends IFormModalAttrs {
    discussion: Discussion;
}
/**
 * `FormModal`, not `Modal`: only its wrapper is a `<form>`, and without one a
 * submit button has no form owner, so `onsubmit` never fires.
 */
export default class ManualReferenceModal<CustomAttrs extends IManualReferenceModalAttrs = IManualReferenceModalAttrs> extends FormModal<CustomAttrs> {
    private targetId;
    private relation;
    private note;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    onsubmit(event: SubmitEvent): void;
}
