import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class ReferencedByGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
}
