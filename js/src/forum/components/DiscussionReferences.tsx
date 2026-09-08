import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import ReferenceListItem from './ReferenceListItem';
import ReferenceListModal from './ReferenceListModal';
import ReferenceGraphModal from './ReferenceGraphModal';
import type Reference from '../../common/models/Reference';

export interface IDiscussionReferencesAttrs extends ComponentAttrs {
  discussion: Discussion;
}

export default class DiscussionReferences<
  CustomAttrs extends IDiscussionReferencesAttrs = IDiscussionReferencesAttrs,
> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const discussion = this.attrs.discussion;

    const incoming = this.loaded(discussion.referencedBy());
    const outgoing = this.loaded(discussion.outgoingReferences(), true);

    if (!incoming.length && !outgoing.length) return null;

    const incomingCount = discussion.referencedByCount() ?? incoming.length;
    const outgoingCount = discussion.outgoingReferencesCount() ?? outgoing.length;

    return (
      <section className="DiscussionReferences">
        {incoming.length > 0 &&
          this.list(
            app.translator.trans('datlechin-references.forum.discussion.referenced_by_title', { count: incomingCount }),
            incoming,
            incomingCount,
            { targetDiscussion: String(discussion.id()) }
          )}

        {outgoing.length > 0 &&
          this.list(app.translator.trans('datlechin-references.forum.discussion.references_title'), outgoing, outgoingCount, {
            sourceDiscussion: String(discussion.id()),
          })}

        <Button
          className="Button Button--text DiscussionReferences-graph"
          icon="fas fa-project-diagram"
          onclick={() => app.modal.show(ReferenceGraphModal, { discussionId: String(discussion.id()) })}
        >
          {app.translator.trans('datlechin-references.forum.graph.title')}
        </Button>
      </section>
    );
  }

  /**
   * One row per discussion, not per reference. Two posts in the same discussion
   * both carry that discussion's title, so ungrouped they render as the same
   * row twice and read as a bug.
   */
  private loaded(references: false | (Reference | undefined)[], outgoing = false): Reference[] {
    const seen = new Set<string>();
    const rows: Reference[] = [];

    for (const reference of ((references || []) as (Reference | undefined)[]).filter(Boolean) as Reference[]) {
      const discussion = outgoing ? reference.targetDiscussion() : reference.sourceDiscussion();
      const id = discussion ? String(discussion.id()) : null;

      if (!id || seen.has(id)) continue;

      seen.add(id);
      rows.push(reference);
    }

    return rows;
  }

  /**
   * The two directions read the same way to a reader, so they share a list.
   * What differs is which end of the row to name, which is why the item is
   * told which direction it is being shown in.
   */
  private list(
    title: Mithril.Children,
    references: Reference[],
    count: number,
    filter: { targetDiscussion?: string; sourceDiscussion?: string }
  ): Mithril.Children {
    return (
      <div className="DiscussionReferences-section">
        <h3 className="DiscussionReferences-title">{title}</h3>
        <ul className="ReferenceList">
          {references.map((reference) => (
            <ReferenceListItem key={reference.id()} reference={reference} outgoing={'sourceDiscussion' in filter} />
          ))}
        </ul>
        {count > references.length && (
          <Button
            className="Button Button--text DiscussionReferences-more"
            onclick={() => app.modal.show(ReferenceListModal, { filter, modalTitle: title })}
          >
            {app.translator.trans('datlechin-references.forum.discussion.show_all_button')}
          </Button>
        )}
      </div>
    );
  }
}
