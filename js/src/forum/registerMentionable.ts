import app from 'flarum/forum/app';

import DiscussionReferenceMention from './mentionables/DiscussionReferenceMention';

/**
 * `HashMentionFormat` holds an array of sources and is declared extendable,
 * which is how tag mentions already sit there as one of several. Registering
 * ours beside it costs no dropdown, no keyboard handling and no caret maths.
 *
 * Without flarum/mentions there is no dropdown and the syntax still parses.
 */
export default function registerMentionable() {
  if (!('flarum-mentions' in flarum.extensions)) return;

  const format = (app as any).mentionFormats?.get('#');

  format?.extend(DiscussionReferenceMention);
}
