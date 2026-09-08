import app from 'flarum/admin/app';
import Link from 'flarum/common/components/Link';
import Placeholder from 'flarum/common/components/Placeholder';
import humanTime from 'flarum/common/helpers/humanTime';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';

import RemoteComponent from '../../common/components/RemoteComponent';
import { trans } from '../config';

type BrokenReference = {
  id: number;
  targetType: string;
  targetId: number;
  brokenAt: string | null;
  sourcePostNumber: number | null;
  sourceDiscussion: { id: number; title: string; slug: string } | null;
};

type Response = { data: BrokenReference[]; meta: { total: number } };

export default class BrokenReferences<CustomAttrs extends ComponentAttrs = ComponentAttrs> extends RemoteComponent<Response, CustomAttrs> {
  request() {
    return app.request<Response>({
      method: 'GET',
      url: `${app.forum.attribute('apiUrl')}/datlechin-references/broken`,
    });
  }

  wrapper(body: Mithril.Children): Mithril.Children {
    return (
      <section className="ExtensionPage-settings BrokenReferences">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{trans('broken.title')}</h2>
          </div>
        </div>
        <div className="container">
          <p className="helpText">{trans('broken.description')}</p>
          {body}
        </div>
      </section>
    );
  }

  failure(): Mithril.Children {
    return <Placeholder text={trans('broken.unavailable')} />;
  }

  content(response: Response): Mithril.Children {
    if (!response.data.length) return <Placeholder text={trans('broken.empty')} />;

    return (
      <div className="BrokenReferences-tableContainer">
        <table className="BrokenReferences-table">
          <thead>
            <tr>
              <th>{trans('broken.target_column')}</th>
              <th>{trans('broken.source_column')}</th>
              <th>{trans('broken.broken_at_column')}</th>
            </tr>
          </thead>
          <tbody>
            {response.data.map((row) => (
              <tr key={row.id}>
                <td>{trans(`target.${row.targetType}`, { id: row.targetId })}</td>
                <td>{this.source(row)}</td>
                <td>{row.brokenAt ? humanTime(new Date(row.brokenAt)) : null}</td>
              </tr>
            ))}
          </tbody>
        </table>
        {response.meta.total > response.data.length && (
          <p className="helpText">{trans('broken.truncated', { shown: response.data.length, total: response.meta.total })}</p>
        )}
      </div>
    );
  }

  private source(row: BrokenReference): Mithril.Children {
    const discussion = row.sourceDiscussion;

    if (!discussion) return null;

    // The admin runs on the same origin as the forum, so this is an ordinary
    // route rather than an external link.
    return (
      <Link external href={`${app.forum.attribute('baseUrl')}/d/${discussion.slug}`} target="_blank">
        {discussion.title}
      </Link>
    );
  }
}
