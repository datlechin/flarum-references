import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import LinksModal, { type LinksTab } from './LinksModal';
import { relationBadge } from '../utils/relations';
import type Reference from '../../common/models/Reference';

export interface IDiscussionLinksAttrs extends ComponentAttrs {
  discussion: Discussion;
}

/**
 * The sidebar block: discussions that link here, and discussions this one
 * links to. Titles only. Dates, authors, notes, the map and moderation are in
 * LinksModal, so the sidebar stays short.
 *
 * On a phone the sidebar is a row of buttons above the first post, so the
 * block shrinks to one button there.
 */
export default class DiscussionLinks<CustomAttrs extends IDiscussionLinksAttrs = IDiscussionLinksAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const discussion = this.attrs.discussion;

    const incoming = this.rows(discussion.referencedBy(), 'incoming');
    const outgoing = this.rows(discussion.outgoingReferences(), 'outgoing');

    if (!incoming.length && !outgoing.length) return null;

    const incomingCount = discussion.referencedByCount() ?? incoming.length;
    const outgoingCount = discussion.outgoingReferencesCount() ?? outgoing.length;
    const firstTab: LinksTab = incoming.length ? 'incoming' : 'outgoing';

    return (
      <div className="DiscussionLinks">
        <Button className="Button DiscussionLinks-summary" icon="fas fa-link" onclick={() => this.open(firstTab)}>
          {app.translator.trans('datlechin-references.forum.links.summary_button', { count: incomingCount + outgoingCount })}
        </Button>

        <div className="DiscussionLinks-body">
          {incoming.length > 0 && this.section('incoming', incoming, incomingCount)}
          {outgoing.length > 0 && this.section('outgoing', outgoing, outgoingCount)}

          <Button className="Button Button--link DiscussionLinks-all" onclick={() => this.open(firstTab)}>
            {app.translator.trans('datlechin-references.forum.links.all_button')}
          </Button>
        </div>
      </div>
    );
  }

  private section(direction: 'incoming' | 'outgoing', references: Reference[], count: number): Mithril.Children {
    return (
      <section className="DiscussionLinks-section">
        <h4 className="DiscussionLinks-heading">
          {app.translator.trans(`datlechin-references.forum.links.${direction}_heading`)}
          <span className="DiscussionLinks-count">{count}</span>
        </h4>
        <ul className="DiscussionLinks-list">
          {references.map((reference) => {
            const other = (direction === 'incoming' ? reference.sourceDiscussion() : reference.targetDiscussion()) as Discussion;
            const post = direction === 'incoming' ? reference.sourcePost() || null : null;

            return (
              <li key={reference.id()}>
                <Link className="DiscussionLinks-item" href={post ? app.route.discussion(other, post.number()) : app.route.discussion(other)}>
                  {other.title()}
                </Link>
                {relationBadge(reference)}
              </li>
            );
          })}
        </ul>
      </section>
    );
  }

  /**
   * One row per other discussion, already grouped by the server. Rows whose
   * other end is missing, or is this discussion, are skipped in case an old
   * payload is still cached.
   */
  private rows(references: false | (Reference | undefined)[], direction: 'incoming' | 'outgoing'): Reference[] {
    const seen = new Set<string>([String(this.attrs.discussion.id())]);
    const rows: Reference[] = [];

    for (const reference of (references || []) as (Reference | undefined)[]) {
      if (!reference || !reference.exists) continue;

      const other = direction === 'incoming' ? reference.sourceDiscussion() : reference.targetDiscussion();
      const id = other ? String(other.id()) : null;

      if (!id || seen.has(id)) continue;

      seen.add(id);
      rows.push(reference);
    }

    return rows;
  }

  private open(tab: LinksTab): void {
    app.modal.show(LinksModal, { discussion: this.attrs.discussion, tab });
  }
}
