import app from 'flarum/admin/app';
import Button from 'flarum/common/components/Button';
import Placeholder from 'flarum/common/components/Placeholder';
import formatNumber from 'flarum/common/utils/formatNumber';
import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';

import RemoteComponent from '../../common/components/RemoteComponent';
import { trans, transText } from '../config';

type Stats = {
  daily: { date: string; count: number }[];
  byRelationType: Record<string, number>;
  byOrigin: Record<string, number>;
  totals: { references: number; broken: number; manual: number };
};

export default class ReferenceAnalytics<CustomAttrs extends ComponentAttrs = ComponentAttrs> extends RemoteComponent<{ data: Stats }, CustomAttrs> {
  request() {
    return app.request<{ data: Stats }>({
      method: 'GET',
      url: `${app.forum.attribute('apiUrl')}/datlechin-references/stats`,
    });
  }

  // Core's own standalone section band, the one the package manager uses to
  // title its queue. Drawn whether or not the request landed, so the page does
  // not start as an unlabelled spinner and then reflow.
  wrapper(body: Mithril.Children): Mithril.Children {
    return (
      <section className="ExtensionPage-settings ReferenceAnalytics">
        <div className="ExtensionPage-permissions-header">
          <div className="container">
            <h2 className="ExtensionTitle">{trans('analytics.title')}</h2>
          </div>
        </div>
        <div className="container">{body}</div>
      </section>
    );
  }

  failure(): Mithril.Children {
    return <Placeholder text={trans('analytics.unavailable')} />;
  }

  content(response: { data: Stats }): Mithril.Children {
    const { totals, byOrigin, byRelationType, daily } = response.data;
    const peak = Math.max(1, ...daily.map((day) => day.count));

    return (
      <>
        <div className="ReferenceAnalytics-totals">
          {this.total('total_label', totals.references)}
          {this.total('broken_label', totals.broken)}
          {this.total('manual_label', totals.manual)}
        </div>

        {daily.length > 0 ? (
          <div
            className="ReferenceAnalytics-chart"
            role="img"
            aria-label={transText('analytics.chart_label', { count: daily.length, peak: formatNumber(peak) })}
          >
            {daily.map((day) => (
              <span
                key={day.date}
                className="ReferenceAnalytics-bar"
                style={{ height: `${Math.round((day.count / peak) * 100)}%` }}
                title={`${day.date}: ${day.count}`}
              />
            ))}
          </div>
        ) : (
          <Placeholder text={trans('analytics.empty')} />
        )}

        <div className="ReferenceAnalytics-breakdowns">
          {this.breakdown('by_origin_label', byOrigin, 'origin')}
          {this.breakdown('by_relation_label', byRelationType, 'relation')}
        </div>

        <Button className="Button" icon="fas fa-download" onclick={() => this.export()}>
          {trans('analytics.export_button')}
        </Button>
      </>
    );
  }

  private total(key: string, value: number): Mithril.Children {
    return (
      <div className="ReferenceAnalytics-total">
        <div className="ReferenceAnalytics-totalLabel">{trans(`analytics.${key}`)}</div>
        <div className="ReferenceAnalytics-totalValue">{formatNumber(value)}</div>
      </div>
    );
  }

  private breakdown(key: string, counts: Record<string, number>, prefix: string): Mithril.Children {
    const entries = Object.entries(counts);

    if (!entries.length) return null;

    return (
      <div className="ReferenceAnalytics-breakdown">
        <h3 className="ReferenceAnalytics-breakdownTitle">{trans(`analytics.${key}`)}</h3>
        <ul className="ReferenceAnalytics-breakdownList">
          {entries.map(([name, count]) => (
            <li key={name} className="ReferenceAnalytics-breakdownRow">
              <span className="ReferenceAnalytics-breakdownName">{trans(`${prefix}.${name}`)}</span>
              <span className="ReferenceAnalytics-breakdownCount">{formatNumber(count)}</span>
            </li>
          ))}
        </ul>
      </div>
    );
  }

  // Left to the browser rather than opened in a tab, so the server's own
  // dated filename survives instead of being replaced by the URL.
  private export(): void {
    window.location.assign(`${app.forum.attribute('apiUrl')}/datlechin-references/stats/export`);
  }
}
