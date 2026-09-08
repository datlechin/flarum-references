import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Link from 'flarum/common/components/Link';
import Icon from 'flarum/common/components/Icon';
import humanTime from 'flarum/common/helpers/humanTime';
import username from 'flarum/common/helpers/username';
import type Mithril from 'mithril';

import type Reference from '../../common/models/Reference';
import relationLabel from '../utils/relationLabel';

export interface IReferenceListItemAttrs extends ComponentAttrs {
  reference: Reference;
  /** Name the far end rather than the near one, for a list of what this discussion cites. */
  outgoing?: boolean;
  onclick?: () => void;
}

export default class ReferenceListItem<CustomAttrs extends IReferenceListItemAttrs = IReferenceListItemAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const reference = this.attrs.reference;
    const post = reference.sourcePost() || null;

    const discussion = this.attrs.outgoing ? reference.targetDiscussion() : reference.sourceDiscussion();

    if (!discussion) return null;

    const href = !this.attrs.outgoing && post ? app.route.discussion(discussion, post.number()) : app.route.discussion(discussion);
    // A reference recorded against a discussion rather than one of its posts
    // still has someone who wrote it: whoever started the discussion.
    const author = (post ? post.user() : null) || discussion.user();
    const createdAt = reference.createdAt();

    return (
      <li>
        <Link href={href} className="ReferenceList-link" onclick={this.attrs.onclick}>
          <Icon name={this.attrs.outgoing ? 'fas fa-arrow-right' : 'fas fa-quote-left'} className="ReferenceList-icon" />
          <span className="ReferenceList-title">{discussion.title()}</span>
        </Link>
        <span className="ReferenceList-meta">
          {relationLabel(reference)}
          {reference.note() && <span className="ReferenceList-note">{reference.note()}</span>}
          {!this.attrs.outgoing && author && username(author)}
          {createdAt && humanTime(createdAt)}
        </span>
      </li>
    );
  }
}
