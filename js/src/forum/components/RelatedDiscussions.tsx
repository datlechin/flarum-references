import app from 'flarum/forum/app';
import Link from 'flarum/common/components/Link';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';

import RemoteComponent from '../../common/components/RemoteComponent';

type RelatedDiscussion = {
  id: number;
  title: string;
  slug: string;
  commentCount: number;
  referencesCount: number;
};

export interface IRelatedDiscussionsAttrs extends ComponentAttrs {
  discussionId: string;
}

export default class RelatedDiscussions<CustomAttrs extends IRelatedDiscussionsAttrs = IRelatedDiscussionsAttrs> extends RemoteComponent<
  { data: RelatedDiscussion[] },
  CustomAttrs
> {
  request() {
    return app.request<{ data: RelatedDiscussion[] }>({
      method: 'GET',
      url: `${app.forum.attribute('apiUrl')}/datlechin-references/related`,
      params: { id: this.attrs.discussionId },
    });
  }

  content(response: { data: RelatedDiscussion[] }): Mithril.Children {
    if (!response.data.length) return null;

    return (
      <section className="RelatedDiscussions DiscussionLinks-section">
        <h4 className="DiscussionLinks-heading">{app.translator.trans('datlechin-references.forum.related.title')}</h4>
        <ul className="DiscussionLinks-list">
          {response.data.map((discussion) => (
            <li key={discussion.id}>
              <Link href={app.route('discussion', { id: discussion.slug })} className="DiscussionLinks-item">
                {discussion.title}
              </Link>
            </li>
          ))}
        </ul>
      </section>
    );
  }
}
