import { BooleanGambit } from 'flarum/common/query/IGambit';
export default class HasReferencesGambit extends BooleanGambit {
    key(): string;
    booleanKey(): 'has';
    filterKey(): string;
}
