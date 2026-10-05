import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import Placeholder from 'flarum/common/components/Placeholder';
import classList from 'flarum/common/utils/classList';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';

import ReferenceListState, { type ReferenceListFilter } from '../states/ReferenceListState';
import LinkListItem from './LinkListItem';
import LinkFormModal from './LinkFormModal';
import ReferenceGraph from './ReferenceGraph';

export type LinksTab = 'incoming' | 'outgoing' | 'map';

export interface ILinksModalAttrs extends IInternalModalAttrs {
  /** Show the links of a discussion, with tabs. */
  discussion?: Discussion;
  /** Or the discussions that link to one post. */
  post?: Post;
  tab?: LinksTab;
}

/**
 * Every link of a discussion, or of a post, with details. It is also the one
 * place a moderator adds, edits and removes links.
 */
export default class LinksModal<CustomAttrs extends ILinksModalAttrs = ILinksModalAttrs> extends Modal<CustomAttrs> {
  // A plain property rather than Modal's state generic: `app.modal.show()`
  // only accepts a class whose state is undefined.
  protected tab: LinksTab = 'incoming';
  protected lists: Partial<Record<LinksTab, ReferenceListState>> = {};

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.tab = this.attrs.post ? 'incoming' : this.attrs.tab || 'incoming';
  }

  className(): string {
    return 'LinksModal Modal--medium';
  }

  title(): Mithril.Children {
    return app.translator.trans(`datlechin-references.forum.links.${this.attrs.post ? 'post_modal_title' : 'modal_title'}`);
  }

  content(): Mithril.Children {
    const list = this.tab === 'map' ? null : this.list(this.tab);

    return (
      <>
        <div className="Modal-body">
          {this.attrs.discussion && this.toolbar(this.attrs.discussion)}

          {this.tab === 'map' && this.attrs.discussion ? (
            <ReferenceGraph discussionId={String(this.attrs.discussion.id())} />
          ) : (
            list && this.rows(list)
          )}
        </div>

        {list && list.hasNext() && (
          <div className="Modal-footer">
            <Button className="Button Button--block" loading={list.isLoadingNext()} onclick={() => list.loadNext().then(() => m.redraw())}>
              {app.translator.trans('datlechin-references.forum.links.load_more_button')}
            </Button>
          </div>
        )}
      </>
    );
  }

  private toolbar(discussion: Discussion): Mithril.Children {
    const tabs: { key: LinksTab; count?: number }[] = [
      { key: 'incoming', count: discussion.referencedByCount() ?? undefined },
      { key: 'outgoing', count: discussion.outgoingReferencesCount() ?? undefined },
      { key: 'map' },
    ];

    return (
      <div className="LinksModal-toolbar">
        <div className="LinksModal-tabs" role="tablist">
          {tabs.map(({ key, count }) => (
            <Button
              className={classList('Button Button--flat LinksModal-tab', { active: this.tab === key })}
              role="tab"
              aria-selected={this.tab === key}
              onclick={() => (this.tab = key)}
            >
              {app.translator.trans(`datlechin-references.forum.links.${key}_tab`)}
              {count !== undefined && <span className="LinksModal-tabCount">{count}</span>}
            </Button>
          ))}
        </div>

        {discussion.attribute<boolean>('canManageReferences') && (
          <Button className="Button Button--primary LinksModal-add" icon="fas fa-plus" onclick={() => this.add(discussion)}>
            {app.translator.trans('datlechin-references.forum.links.add_button')}
          </Button>
        )}
      </div>
    );
  }

  private rows(list: ReferenceListState): Mithril.Children {
    if (list.isInitialLoading()) return <LoadingIndicator />;

    const references = list.references();

    if (!references.length) return <Placeholder text={app.translator.trans('datlechin-references.forum.links.empty')} />;

    return (
      <ul className="LinkList">
        {references.map((reference) => (
          <LinkListItem key={reference.id()} reference={reference} outgoing={this.tab === 'outgoing'} onchange={() => this.changed()} />
        ))}
      </ul>
    );
  }

  /**
   * Created on first view of a tab and kept, so switching back does not
   * reload it.
   */
  private list(tab: LinksTab): ReferenceListState {
    if (!this.lists[tab]) {
      const list = new ReferenceListState({ filter: this.filter(tab), sort: '-createdAt' });

      list.refresh();
      this.lists[tab] = list;
    }

    return this.lists[tab]!;
  }

  private filter(tab: LinksTab): ReferenceListFilter {
    if (this.attrs.post) return { target: `posts:${this.attrs.post.id()}` };

    const id = String(this.attrs.discussion!.id());

    return tab === 'outgoing' ? { outgoing: id } : { incoming: id };
  }

  private add(discussion: Discussion): void {
    app.modal.show(LinkFormModal, { discussion, onsave: () => this.changed() }, true);
  }

  /**
   * Lists and counts are worked out per reader on the server, so after a
   * change both are asked for again.
   */
  private changed(): void {
    this.lists = {};

    const discussion = this.attrs.discussion;

    if (discussion) {
      app.store.find('discussions', String(discussion.id())).then(() => m.redraw());
    }

    m.redraw();
  }
}
