import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import Link from 'flarum/common/components/Link';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

import ReferenceListModal from './components/ReferenceListModal';
import type Reference from '../common/models/Reference';

const PREVIEW = 3;

export default function addBacklinkFooterItem() {
  extend(CommentPost.prototype, 'footerItems', function (items: ItemList<Mithril.Children>) {
    const post = this.attrs.post;
    const references = ((post.referencedBy() || []) as (Reference | undefined)[]).filter(Boolean) as Reference[];

    if (!references.length) return;

    // Mentions puts its own "replied to this" block under this key, and ours
    // covers the same ground and more. Removing it before the check above cost
    // every post that had replies but no references the line mentions had
    // already drawn. Our bundle runs after theirs because composer.json
    // declares flarum/mentions as an optional dependency.
    if ('flarum-mentions' in flarum.extensions) {
      items.remove('replies');
    }

    const count = post.referencedByCount() ?? references.length;

    const names: Mithril.Children[] = references
      .slice(0, PREVIEW)
      .map((reference) => {
        const discussion = reference.sourceDiscussion();
        const sourcePost = reference.sourcePost() || null;

        if (!discussion) return null;

        return (
          <Link href={sourcePost ? app.route.discussion(discussion, sourcePost.number()) : app.route.discussion(discussion)}>
            {discussion.title()}
          </Link>
        );
      })
      .filter(Boolean);

    // Counted from what actually rendered, so a reference whose discussion the
    // reader cannot open folds into the overflow instead of vanishing.
    const overflow = count - names.length;

    if (overflow > 0) {
      names.push(
        <Button
          className="Button Button--text"
          onclick={() =>
            app.modal.show(ReferenceListModal, {
              filter: { target: `posts:${post.id()}` },
              modalTitle: app.translator.trans('datlechin-references.forum.discussion.referenced_by_title', { count }),
            })
          }
        >
          {app.translator.trans('datlechin-references.forum.post.others_text', { count: overflow })}
        </Button>
      );
    }

    // The icon is a sibling of the sentence, never inside the link: core hides
    // an icon nested in a footer link and sizes a bare one for us.
    items.add(
      'references',
      <div className="Post-referencedBy">
        <Icon name="fas fa-link" />
        {app.translator.trans('datlechin-references.forum.post.referenced_by_text', {
          count,
          discussions: punctuateSeries(names),
        })}
      </div>
    );
  });
}
