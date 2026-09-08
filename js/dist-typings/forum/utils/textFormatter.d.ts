type FormatterTag = {
    getAttribute(name: string): string;
    setAttribute(name: string, value: string | boolean): void;
    invalidate(): void;
};
/**
 * The twin of `ConfigureDiscussionReferences::addDiscussionTitle()`.
 *
 * The composer preview is parsed in the browser and never reaches the server,
 * so this decides what a writer sees while typing. Nothing enforces that the
 * two agree; the Jest suite feeds both the same input.
 *
 * Only what the store already holds can answer here, so a reference to a
 * discussion this page has never loaded stays as typed until it is posted.
 */
export declare function filterDiscussionReferences(tag: FormatterTag): boolean;
/**
 * The twin of `ConfigureDiscussionReferences::dummyFilter()`, and the reason
 * the preview stops drawing every live reference in the deleted style: the
 * server sets `deleted` from the database, and in the browser nothing does, so
 * the template's `@deleted != 1` test was reading an absent attribute.
 *
 * Runs after attribute filtering, because a value of exactly `false` is dropped
 * there.
 */
export declare function postFilterDiscussionReferences(tag: FormatterTag): boolean;
export {};
