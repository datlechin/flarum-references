import app from 'flarum/forum/app';

import type Reference from '../../common/models/Reference';

/**
 * A plain `references` row says nothing a reader needs, so it shows nothing.
 * Only a moderator's classification earns a label.
 */
export default function relationLabel(reference: Reference) {
  const relation = reference.relationType();

  if (!relation || relation === 'references') return null;

  return <span className="ReferenceList-relation">{app.translator.trans(`datlechin-references.forum.relation.${relation}`)}</span>;
}
