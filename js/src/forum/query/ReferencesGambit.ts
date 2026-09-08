import app from 'flarum/forum/app';
import { KeyValueGambit } from 'flarum/common/query/IGambit';

export default class ReferencesGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('datlechin-references.lib.gambits.references.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('datlechin-references.lib.gambits.references.hint', {}, true);
  }

  filterKey(): string {
    return 'references';
  }
}
