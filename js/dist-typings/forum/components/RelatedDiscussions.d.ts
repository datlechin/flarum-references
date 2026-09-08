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
export default class RelatedDiscussions<CustomAttrs extends IRelatedDiscussionsAttrs = IRelatedDiscussionsAttrs> extends RemoteComponent<{
    data: RelatedDiscussion[];
}, CustomAttrs> {
    request(): Promise<{
        data: RelatedDiscussion[];
    }>;
    content(response: {
        data: RelatedDiscussion[];
    }): Mithril.Children;
}
export {};
