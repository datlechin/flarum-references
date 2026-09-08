import app from 'flarum/forum/app';
import { KeyValueGambit } from 'flarum/common/query/IGambit';

export default class ReferencedByGambit extends KeyValueGambit {
  key(): string {
    return app.translator.trans('datlechin-references.lib.gambits.referenced_by.key', {}, true);
  }

  hint(): string {
    return app.translator.trans('datlechin-references.lib.gambits.referenced_by.hint', {}, true);
  }

  filterKey(): string {
    return 'referencedBy';
  }
}
