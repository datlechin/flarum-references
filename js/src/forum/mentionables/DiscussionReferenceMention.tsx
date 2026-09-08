import app from 'flarum/forum/app';
import Badge from 'flarum/common/components/Badge';
import highlight from 'flarum/common/helpers/highlight';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import cleanTitle from '../utils/cleanTitle';

/**
 * Typed with `#`, stored as `@"Title"#d123`.
 *
 * The trigger key and the stored form do not have to match, and already do
 * not: a post mention triggers on `@` and stores `@"Name"#p12`. Keeping `#`
 * means "look this up by name", which is what it already does for tags.
 *
 * Shaped to mentions' `MentionableModel` without importing it, so nothing here
 * needs that extension installed to compile or to load.
 */
export default class DiscussionReferenceMention {
  public format: { format(...args: string[]): string };

  constructor(format: { format(...args: string[]): string }) {
    this.format = format;
  }

  type(): string {
    return 'discussion-reference';
  }

  initialResults(): Discussion[] {
    return [];
  }

  replacement(discussion: Discussion): string {
    return `@"${cleanTitle(discussion.title())}"#d${discussion.id()}`;
  }

  matches(model: Discussion, typed: string): boolean {
    if (!typed) return false;

    return String(model.id()) === typed || model.title().toLowerCase().startsWith(typed.toLowerCase());
  }

  maxStoreMatchedResults(): null {
    return null;
  }

  async search(typed: string): Promise<Discussion[]> {
    // A bare number is almost always an id rather than a title, so it is
    // looked up directly and only falls back to a search when nothing has it.
    if (/^\d+$/.test(typed)) {
      const byId = await app.store.find<Discussion>('discussions', typed).catch(() => null);

      if (byId) return [byId];
    }

    return await app.store.find<Discussion[]>('discussions', { filter: { q: typed }, page: { limit: 5 } });
  }

  suggestion(model: Discussion, typed: string): Mithril.Children {
    return (
      <>
        <Badge className="Avatar" icon="fas fa-comments" />
        <span className="username">{typed ? highlight(model.title(), typed) : model.title()}</span>
      </>
    );
  }

  enabled(): boolean {
    return !!app.forum.attribute('datlechin-references.shortReferencesEnabled');
  }
}
