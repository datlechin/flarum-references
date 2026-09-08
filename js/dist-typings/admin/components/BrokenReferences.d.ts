import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import RemoteComponent from '../../common/components/RemoteComponent';
type BrokenReference = {
    id: number;
    targetType: string;
    targetId: number;
    brokenAt: string | null;
    sourcePostNumber: number | null;
    sourceDiscussion: {
        id: number;
        title: string;
        slug: string;
    } | null;
};
type Response = {
    data: BrokenReference[];
    meta: {
        total: number;
    };
};
export default class BrokenReferences<CustomAttrs extends ComponentAttrs = ComponentAttrs> extends RemoteComponent<Response, CustomAttrs> {
    request(): Promise<Response>;
    wrapper(body: Mithril.Children): Mithril.Children;
    failure(): Mithril.Children;
    content(response: Response): Mithril.Children;
    private source;
}
export {};
