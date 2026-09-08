import PaginatedListState, { PaginatedListParams } from 'flarum/common/states/PaginatedListState';
import type Reference from '../../common/models/Reference';
export interface ReferenceListParams extends PaginatedListParams {
    filter: {
        target?: string;
        targetDiscussion?: string;
        sourceDiscussion?: string;
    };
    sort?: string;
    page?: {
        offset?: number;
        limit: number;
    };
}
export default class ReferenceListState<P extends ReferenceListParams = ReferenceListParams> extends PaginatedListState<Reference, P> {
    constructor(params: P, page?: number);
    get type(): string;
}
