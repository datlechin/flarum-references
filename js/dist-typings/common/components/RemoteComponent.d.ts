import Component, { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
/**
 * A component whose whole job starts with one request.
 *
 * Four of these read a dedicated endpoint, show a spinner, then draw what came
 * back. Written out each time, the loading flag and the redraw on both the
 * success and failure paths were four chances to forget one.
 *
 * `failure()` decides what a request that did not arrive looks like. A block
 * contributed to someone else's surface says nothing, because a stray error in
 * a sidebar is worse than a gap. A panel the reader opened on purpose overrides
 * it and says something, because an empty panel reads as a bug.
 */
export default abstract class RemoteComponent<T, CustomAttrs extends ComponentAttrs = ComponentAttrs> extends Component<CustomAttrs> {
    protected loading: boolean;
    protected data: T | null;
    abstract request(): Promise<T>;
    abstract content(data: T): Mithril.Children;
    failure(): Mithril.Children;
    /**
     * Chrome drawn whether or not the request landed. A panel that only appears
     * on success shows an unlabelled spinner first and then reflows the page.
     */
    wrapper(body: Mithril.Children): Mithril.Children;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    view(): Mithril.Children;
    protected load(): void;
}
