import app from 'flarum/forum/app';
import Modal, { IInternalModalAttrs } from 'flarum/common/components/Modal';
import type Mithril from 'mithril';

import ReferenceGraph from './ReferenceGraph';

export interface IReferenceGraphModalAttrs extends IInternalModalAttrs {
  discussionId: string;
}

export default class ReferenceGraphModal<CustomAttrs extends IReferenceGraphModalAttrs = IReferenceGraphModalAttrs> extends Modal<CustomAttrs> {
  className(): string {
    return 'ReferenceGraphModal Modal--medium';
  }

  title(): Mithril.Children {
    return app.translator.trans('datlechin-references.forum.graph.title');
  }

  content(): Mithril.Children {
    return (
      <div className="Modal-body">
        <p className="helpText">{app.translator.trans('datlechin-references.forum.graph.description')}</p>
        <ReferenceGraph discussionId={this.attrs.discussionId} />
      </div>
    );
  }
}
