import app from 'flarum/forum/app';
import Component, { ComponentAttrs } from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import humanTime from 'flarum/common/helpers/humanTime';
import username from 'flarum/common/helpers/username';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

import LinkFormModal from './LinkFormModal';
import { relationBadge } from '../utils/relations';
import type Reference from '../../common/models/Reference';

export interface ILinkListItemAttrs extends ComponentAttrs {
  reference: Reference;
  /** Name the discussion this one links to, rather than the one linking here. */
  outgoing?: boolean;
  /** Called after a moderator edits or removes the link. */
  onchange?: () => void;
}

export default class LinkListItem<CustomAttrs extends ILinkListItemAttrs = ILinkListItemAttrs> extends Component<CustomAttrs> {
  view(): Mithril.Children {
    const reference = this.attrs.reference;
    const post = reference.sourcePost() || null;
    const discussion = this.attrs.outgoing ? reference.targetDiscussion() : reference.sourceDiscussion();

    if (!discussion) return null;

    // Incoming links open at the post that made them.
    const href = !this.attrs.outgoing && post ? app.route.discussion(discussion, post.number()) : app.route.discussion(discussion);
    // A link added by hand has no post, so it is credited to whoever added it.
    const author = post ? post.user() : reference.createdBy();
    const createdAt = reference.createdAt();

    return (
      <li className="LinkRow">
        <div className="LinkRow-head">
          <Link href={href} className="LinkRow-title" onclick={() => app.modal.close()}>
            {discussion.title()}
          </Link>
          {relationBadge(reference)}
        </div>

        <div className="LinkRow-meta">
          {author && <span>{app.translator.trans('datlechin-references.forum.links.by_text', { username: username(author) })}</span>}
          {createdAt && humanTime(createdAt)}
          {reference.canEdit() && (
            <Button className="Button Button--link LinkRow-action" onclick={() => this.edit()}>
              {app.translator.trans('datlechin-references.forum.links.edit_button')}
            </Button>
          )}
          {reference.canDelete() && (
            <Button className="Button Button--link LinkRow-action" onclick={() => this.remove()}>
              {app.translator.trans('datlechin-references.forum.links.remove_button')}
            </Button>
          )}
        </div>

        {reference.note() && <p className="LinkRow-note">{reference.note()}</p>}
      </li>
    );
  }

  private edit(): void {
    app.modal.show(LinkFormModal, { reference: this.attrs.reference, onsave: this.attrs.onchange }, true);
  }

  private remove(): void {
    if (!confirm(extractText(app.translator.trans('datlechin-references.forum.links.remove_confirmation')))) return;

    this.attrs.reference.delete().then(() => this.attrs.onchange?.());
  }
}
