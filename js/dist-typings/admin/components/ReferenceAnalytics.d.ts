import type { ComponentAttrs } from 'flarum/common/Component';
import type Mithril from 'mithril';
import RemoteComponent from '../../common/components/RemoteComponent';
type Stats = {
    daily: {
        date: string;
        count: number;
    }[];
    byRelationType: Record<string, number>;
    byOrigin: Record<string, number>;
    totals: {
        references: number;
        broken: number;
        manual: number;
    };
};
export default class ReferenceAnalytics<CustomAttrs extends ComponentAttrs = ComponentAttrs> extends RemoteComponent<{
    data: Stats;
}, CustomAttrs> {
    request(): Promise<{
        data: Stats;
    }>;
    wrapper(body: Mithril.Children): Mithril.Children;
    failure(): Mithril.Children;
    content(response: {
        data: Stats;
    }): Mithril.Children;
    private total;
    private breakdown;
    private export;
}
export {};
