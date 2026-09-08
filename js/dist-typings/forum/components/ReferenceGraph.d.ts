import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import RemoteComponent from '../../common/components/RemoteComponent';
type GraphNode = {
    id: number;
    title: string;
    count: number;
};
type GraphEdge = {
    from: number;
    to: number;
};
type Graph = {
    nodes: GraphNode[];
    edges: GraphEdge[];
};
export interface IReferenceGraphAttrs extends ComponentAttrs {
    discussionId: string;
}
/**
 * Three columns, not a circle: the discussions that cite this one, this one,
 * and the ones it cites. A ring reads as a diagram but says nothing a reader
 * can act on, because a title will not fit beside a dot on a circle. Laid out
 * in rows every node carries its own name.
 */
export default class ReferenceGraph<CustomAttrs extends IReferenceGraphAttrs = IReferenceGraphAttrs> extends RemoteComponent<Graph, CustomAttrs> {
    request(): Promise<Graph>;
    failure(): Mithril.Children;
    content(graph: Graph): Mithril.Children;
    /**
     * Core only routes links it can recognise by class, and an anchor inside an
     * `svg` is not one of them, so this would otherwise reload the whole page and
     * leave the modal behind. A modified click is left to the browser, which is
     * how opening in a new tab keeps working.
     */
    private open;
    /**
     * A column per step, signed by direction: discussions that reach this one to
     * its left, the ones it reaches to its right, and anything two steps out
     * further along the same side.
     *
     * Placing a second-hop discussion in whichever column was shorter claimed a
     * direction it did not have, and an edge between two nodes in one column drew
     * as a vertical line that read as grouping rather than as a link.
     */
    private place;
}
export {};
