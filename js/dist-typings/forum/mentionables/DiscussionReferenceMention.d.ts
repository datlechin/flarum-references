import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
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
    format: {
        format(...args: string[]): string;
    };
    constructor(format: {
        format(...args: string[]): string;
    });
    type(): string;
    initialResults(): Discussion[];
    replacement(discussion: Discussion): string;
    matches(model: Discussion, typed: string): boolean;
    maxStoreMatchedResults(): null;
    search(typed: string): Promise<Discussion[]>;
    suggestion(model: Discussion, typed: string): Mithril.Children;
    enabled(): boolean;
}
