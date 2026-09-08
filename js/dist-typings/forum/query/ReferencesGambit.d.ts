import { KeyValueGambit } from 'flarum/common/query/IGambit';
export default class ReferencesGambit extends KeyValueGambit {
    key(): string;
    hint(): string;
    filterKey(): string;
}
