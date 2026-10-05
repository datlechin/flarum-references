import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import type Reference from '../../common/models/Reference';
/**
 * `incoming` and `outgoing` are one row per other discussion, grouped the way
 * the sidebar preview is; `target` is every row pointing at one post.
 */
export type ReferenceListFilter = {
    incoming: string;
} | {
    outgoing: string;
} | {
    target: string;
};
export interface ReferenceListParams extends PaginatedListParams {
    filter: ReferenceListFilter;
    sort?: string;
    page?: {
        offset?: number;
        limit: number;
    };
}
export default class ReferenceListState<P extends ReferenceListParams = ReferenceListParams> extends PaginatedListState<Reference, P> {
    constructor(params: P, page?: number);
    get type(): string;
    /**
     * What is still there. A row deleted from the list stays in its page until
     * the next load, and a removed model is the one thing that would draw wrong.
     */
    references(): Reference[];
}
