import app from 'flarum/forum/app';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

import type Reference from '../../common/models/Reference';

/**
 * The types a moderator may give a link, read from the forum payload so the
 * form cannot offer one the server would refuse.
 */
export function relationOptions(): Record<string, string> {
  const options: Record<string, string> = {};

  for (const relation of app.forum.attribute<string[]>('datlechin-references.relationTypes') || []) {
    options[relation] = extractText(app.translator.trans(`datlechin-references.forum.relation.${relation}`));
  }

  return options;
}

/**
 * A plain link needs no label; only a type a moderator chose gets one.
 */
export function relationBadge(reference: Reference): Mithril.Children {
  const relation = reference.relationType();

  if (!relation || relation === 'references') return null;

  return <span className="LinkBadge">{app.translator.trans(`datlechin-references.forum.relation.${relation}`)}</span>;
}
