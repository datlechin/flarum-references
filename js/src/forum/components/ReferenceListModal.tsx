import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';

import ReferenceListState from '../states/ReferenceListState';
import ReferenceListItem from './ReferenceListItem';

export interface IReferenceListModalAttrs extends IInternalModalAttrs {
  filter: { target?: string; targetDiscussion?: string; sourceDiscussion?: string };
  modalTitle: Mithril.Children;
}

export default class ReferenceListModal<CustomAttrs extends IReferenceListModalAttrs = IReferenceListModalAttrs> extends Modal<CustomAttrs> {
  // A plain property rather than Modal's state generic: `app.modal.show()`
  // only accepts a class whose state is undefined, so parameterising it there
  // makes the modal unopenable.
  protected list!: ReferenceListState;

  oninit(vnode: Mithril.Vnode<CustomAttrs, this>) {
    super.oninit(vnode);

    this.list = new ReferenceListState({ filter: this.attrs.filter, sort: '-createdAt' });
    this.list.refresh();
  }

  className(): string {
    return 'ReferenceListModal Modal--medium';
  }

  title(): Mithril.Children {
    return this.attrs.modalTitle;
  }

  content(): Mithril.Children {
    return (
      <>
        <div className="Modal-body">
          {this.list.isInitialLoading() ? (
            <LoadingIndicator />
          ) : (
            <ul className="ReferenceList">
              {this.list
                .getPages()
                .flatMap((page) => page.items)
                .map((reference) => (
                  <ReferenceListItem key={reference.id()} reference={reference} onclick={() => app.modal.close()} />
                ))}
            </ul>
          )}
        </div>
        {this.list.hasNext() && (
          <div className="Modal-footer">
            <Button className="Button Button--block" loading={this.list.isLoadingNext()} onclick={() => this.list.loadNext().then(() => m.redraw())}>
              {app.translator.trans('datlechin-references.forum.discussion.show_all_button')}
            </Button>
          </div>
        )}
      </>
    );
  }
}
