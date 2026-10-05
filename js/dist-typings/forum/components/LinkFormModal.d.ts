import FormModal, { IFormModalAttrs } from 'flarum/common/components/FormModal';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
import type Reference from '../../common/models/Reference';
export interface ILinkFormModalAttrs extends IFormModalAttrs {
    /** Add a link from this discussion. */
    discussion?: Discussion;
    /** Or edit this link. */
    reference?: Reference;
    onsave?: () => void;
}
/**
 * Add a link by hand, or change the type and note of an existing one.
 *
 * `FormModal`, not `Modal`: only its wrapper is a `<form>`, and without one a
 * submit button has no form owner, so `onsubmit` never fires.
 */
export default class LinkFormModal<CustomAttrs extends ILinkFormModalAttrs = ILinkFormModalAttrs> extends FormModal<CustomAttrs> {
    private relation;
    private note;
    private query;
    private target;
    private results;
    private searching;
    private timeout?;
    private searchId;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    onremove(vnode: Mithril.VnodeDOM<CustomAttrs, this>): void;
    className(): string;
    title(): Mithril.Children;
    content(): Mithril.Children;
    private picker;
    private selected;
    private pick;
    private search;
    private load;
    onsubmit(event: SubmitEvent): void;
}
