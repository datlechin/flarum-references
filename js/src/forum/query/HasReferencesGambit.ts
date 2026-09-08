import app from 'flarum/forum/app';
import { BooleanGambit } from 'flarum/common/query/IGambit';

export default class HasReferencesGambit extends BooleanGambit {
  key(): string {
    return app.translator.trans('datlechin-references.lib.gambits.has_references.key', {}, true);
  }

  booleanKey(): 'has' {
    return 'has';
  }

  filterKey(): string {
    return 'hasReferences';
  }
}
