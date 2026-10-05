import EventPost from 'flarum/forum/components/EventPost';
/**
 * "Alice referenced this discussion in <discussion>". The citing posts come
 * from the server already narrowed to the ones this reader may open, so a
 * citation from somewhere they cannot see reads as the plain sentence rather
 * than naming a discussion they were never meant to know about.
 */
export default class ReferencedEventPost extends EventPost {
    icon(): string;
    descriptionKey(): string;
    descriptionData(): Record<string, unknown>;
    /**
     * One per citing discussion. Merging keeps one member's citations in one
     * line, and two of theirs from the same thread would name it twice.
     */
    private sources;
}
