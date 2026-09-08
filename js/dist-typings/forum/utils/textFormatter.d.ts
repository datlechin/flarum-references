type FormatterTag = {
    getAttribute(name: string): string;
    setAttribute(name: string, value: string): void;
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
export {};
