import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import Link from 'flarum/common/components/Link';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import ItemList from 'flarum/common/utils/ItemList';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import LinksModal from './components/LinksModal';
import type Reference from '../common/models/Reference';

/**
 * "Linked from <discussion>, <discussion>" under a post another discussion
 * links to.
 *
 * Beside Mentions' "replied to this", never instead of it. Replies are a
 * conversation inside the discussion and Mentions owns them; removing its list
 * to draw ours put every reply under the post as the discussion referencing
 * itself. The server leaves post mentions out of this list for the same
 * reason, so the two never name the same post.
 */
export default function addBacklinkFooterItem() {
  extend(CommentPost.prototype, 'footerItems', function (items: ItemList<Mithril.Children>) {
    const post = this.attrs.post;
    const own = post.discussion();
    const discussionId = own ? own.id() : undefined;

    // Rows from before same-discussion links stopped being recorded may still
    // sit in a cached payload, so a row from this post's own discussion is
    // dropped here as well.
    const references = ((post.referencedBy() || []) as (Reference | undefined)[]).filter((reference): reference is Reference => {
      const source = reference && reference.sourceDiscussion();

      return !!source && source.id() !== discussionId;
    });

    if (!references.length) return;

    // One name per citing discussion: two posts in the same thread citing this
    // one would otherwise print its title twice in one sentence.
    const seen = new Set<string>();
    const names: Mithril.Children[] = [];

    for (const reference of references) {
      const discussion = reference.sourceDiscussion() as Discussion;
      const id = String(discussion.id());

      if (seen.has(id)) continue;
      seen.add(id);

      const sourcePost = reference.sourcePost() || null;

      names.push(
        <Link href={sourcePost ? app.route.discussion(discussion, sourcePost.number()) : app.route.discussion(discussion)}>{discussion.title()}</Link>
      );
    }

    // Counted in citing posts, not discussions: the server counts rows, and
    // only the preview has been grouped. The wording says "more" rather than
    // "others" so it does not read as that many more discussions.
    const count = post.referencedByCount() ?? references.length;
    const remaining = count - references.length;

    items.add(
      'references',
      <div className="Post-referencedBy">
        <Icon name="fas fa-link" />
        <span className="Post-referencedBy-summary">
          {app.translator.trans('datlechin-references.forum.post.linked_from_text', { discussions: punctuateSeries(names) })}
        </span>
        {remaining > 0 && (
          <Button className="Button Button--text Post-referencedBy-more" onclick={() => app.modal.show(LinksModal, { post })}>
            {app.translator.trans('datlechin-references.forum.post.more_button', { count: remaining })}
          </Button>
        )}
      </div>
    );
  });
}
