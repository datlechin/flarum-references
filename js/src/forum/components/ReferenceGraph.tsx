import app from 'flarum/forum/app';
import Placeholder from 'flarum/common/components/Placeholder';
import classList from 'flarum/common/utils/classList';
import { truncate } from 'flarum/common/utils/string';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';

import RemoteComponent from '../../common/components/RemoteComponent';

type GraphNode = { id: number; title: string; count: number };
type GraphEdge = { from: number; to: number };
type Graph = { nodes: GraphNode[]; edges: GraphEdge[] };

type Placed = GraphNode & { x: number; y: number; room: number };

const ROW = 72;
const PADDING = 16;
const COLUMN = 240;
const RADIUS = 5;
const ARROW = 7;
// SVG text neither wraps nor ellipsises, so a title is cut to what its column
// holds. A 12px sans character averages under 7px, and going over means two
// columns' labels run into each other, so the estimate errs wide.
const PER_PIXEL = 1 / 7;

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
  request() {
    return app.request<Graph>({
      method: 'GET',
      url: `${app.forum.attribute('apiUrl')}/datlechin-references/graph`,
      params: { id: this.attrs.discussionId },
    });
  }

  failure(): Mithril.Children {
    return <Placeholder text={app.translator.trans('datlechin-references.forum.graph.empty')} />;
  }

  content(graph: Graph): Mithril.Children {
    if (graph.nodes.length < 2) return this.failure();

    const centreId = Number(this.attrs.discussionId);
    const placed = this.place(graph, centreId);
    const byId = new Map(placed.map((node) => [node.id, node]));

    // Resolved before mapping, not inside it: a vnode list has to be all keyed
    // or none keyed, and an edge pointing at a node the server capped out of
    // the payload would otherwise return an unkeyed null among keyed siblings.
    const edges = graph.edges
      .map((edge) => ({ edge, from: byId.get(edge.from), to: byId.get(edge.to) }))
      .filter((e): e is { edge: GraphEdge; from: Placed; to: Placed } => !!e.from && !!e.to);

    const height = Math.max(...placed.map((node) => node.y)) + ROW / 2 + PADDING;
    const width = Math.max(...placed.map((node) => node.x)) + COLUMN / 2 + PADDING;

    return (
      <svg
        className="ReferenceGraph"
        viewBox={`0 0 ${width} ${height}`}
        // Sized as well as scaled: with only a viewBox the drawing stretches to
        // whatever width it is given, and a 12px label comes out at whatever
        // that magnification makes it. At its own size the label is the size it
        // was written to be, and a narrow screen still scales it down.
        width={width}
        height={height}
        role="img"
        aria-label={app.translator.trans('datlechin-references.forum.graph.accessible_label', { count: graph.nodes.length }, true)}
      >
        <defs>
          <marker id="ReferenceGraph-arrow" markerWidth={ARROW} markerHeight={ARROW} refX={ARROW} refY={ARROW / 2} orient="auto">
            <path className="ReferenceGraph-arrowHead" d={`M0,0 L${ARROW},${ARROW / 2} L0,${ARROW} z`} />
          </marker>
        </defs>

        {edges.map(({ edge, from, to }) => {
          // Stopped short of the node so the head sits against it rather than
          // under it, and measured along the edge so a diagonal is trimmed by
          // the same amount as a horizontal one.
          const dx = to.x - from.x;
          const dy = to.y - from.y;
          const length = Math.hypot(dx, dy) || 1;
          const trim = (RADIUS + ARROW) / length;

          return (
            <line
              key={`${edge.from}-${edge.to}`}
              className="ReferenceGraph-edge"
              marker-end="url(#ReferenceGraph-arrow)"
              x1={from.x + dx * (RADIUS / length)}
              y1={from.y + dy * (RADIUS / length)}
              x2={to.x - dx * trim}
              y2={to.y - dy * trim}
            />
          );
        })}

        {placed.map((node) => {
          const centre = node.id === centreId;

          return (
            <a
              key={node.id}
              href={app.route('discussion', { id: node.id })}
              className="ReferenceGraph-link"
              onclick={(event: MouseEvent) => this.open(event, node.id)}
            >
              <circle
                className={classList('ReferenceGraph-node', { 'ReferenceGraph-node--centre': centre })}
                cx={node.x}
                cy={node.y}
                r={centre ? 7 : RADIUS}
              />
              <text
                className={classList('ReferenceGraph-label', { 'ReferenceGraph-label--centre': centre })}
                x={node.x}
                y={node.y + 22}
                text-anchor="middle"
              >
                {truncate(node.title, Math.floor(node.room * PER_PIXEL))}
                <title>{node.title}</title>
              </text>
            </a>
          );
        })}
      </svg>
    );
  }

  /**
   * Core only routes links it can recognise by class, and an anchor inside an
   * `svg` is not one of them, so this would otherwise reload the whole page and
   * leave the modal behind. A modified click is left to the browser, which is
   * how opening in a new tab keeps working.
   */
  private open(event: MouseEvent, id: number): void {
    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

    event.preventDefault();

    app.modal.close();
    m.route.set(app.route('discussion', { id }));
  }

  /**
   * A column per step, signed by direction: discussions that reach this one to
   * its left, the ones it reaches to its right, and anything two steps out
   * further along the same side.
   *
   * Placing a second-hop discussion in whichever column was shorter claimed a
   * direction it did not have, and an edge between two nodes in one column drew
   * as a vertical line that read as grouping rather than as a link.
   */
  private place(graph: Graph, centreId: number): Placed[] {
    const inbound = new Map<number, number[]>();
    const outbound = new Map<number, number[]>();

    for (const edge of graph.edges) {
      (outbound.get(edge.from) ?? outbound.set(edge.from, []).get(edge.from)!).push(edge.to);
      (inbound.get(edge.to) ?? inbound.set(edge.to, []).get(edge.to)!).push(edge.from);
    }

    const column = new Map<number, number>([[centreId, 0]]);

    // One step out each way, then a second along the same side.
    const walk = (from: number[], edges: Map<number, number[]>, sign: number, depth: number): number[] => {
      const next: number[] = [];

      for (const id of from) {
        for (const neighbour of edges.get(id) ?? []) {
          if (column.has(neighbour)) continue;

          column.set(neighbour, sign * depth);
          next.push(neighbour);
        }
      }

      return next;
    };

    walk(walk([centreId], inbound, -1, 1), inbound, -1, 2);
    walk(walk([centreId], outbound, 1, 1), outbound, 1, 2);

    // Anything the walk never reached is in the payload without a path back to
    // the middle, so it sits one step out rather than claiming a direction.
    for (const node of graph.nodes) {
      if (!column.has(node.id)) column.set(node.id, 1);
    }

    const columns = new Map<number, GraphNode[]>();

    for (const node of graph.nodes) {
      const index = column.get(node.id) ?? 0;

      (columns.get(index) ?? columns.set(index, []).get(index)!).push(node);
    }

    const indices = [...columns.keys()].sort((a, b) => a - b);
    const rows = Math.max(...[...columns.values()].map((c) => c.length));
    const placed: Placed[] = [];

    indices.forEach((index, position) => {
      const nodes = columns.get(index) ?? [];
      // Centred against the tallest column, so a short one sits opposite the
      // middle of the long one instead of stacking from the top.
      const offset = (rows - nodes.length) / 2;

      nodes.forEach((node, row) => {
        placed.push({
          ...node,
          x: PADDING + COLUMN / 2 + position * COLUMN,
          room: COLUMN - 12,
          y: PADDING + (offset + row + 0.5) * ROW,
        });
      });
    });

    return placed;
  }
}
