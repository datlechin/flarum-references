import app from 'flarum/forum/app';
import EventPost from 'flarum/forum/components/EventPost';
import Link from 'flarum/common/components/Link';
import punctuateSeries from 'flarum/common/helpers/punctuateSeries';
import type Post from 'flarum/common/models/Post';
import type Mithril from 'mithril';

/**
 * "Alice referenced this discussion in <discussion>". The citing posts come
 * from the server already narrowed to the ones this reader may open, so a
 * citation from somewhere they cannot see reads as the plain sentence rather
 * than naming a discussion they were never meant to know about.
 */
export default class ReferencedEventPost extends EventPost {
  icon(): string {
    return 'fas fa-link';
  }

  descriptionKey(): string {
    return this.sources().length ? 'datlechin-references.forum.event_post.linked_from' : 'datlechin-references.forum.event_post.linked';
  }

  descriptionData(): Record<string, unknown> {
    const links = this.sources().map((source) => (
      <Link href={app.route.discussion(source.discussion(), source.number())}>{source.discussion().title()}</Link>
    ));

    return { discussions: punctuateSeries(links) };
  }

  /**
   * One per citing discussion. Merging keeps one member's citations in one
   * line, and two of theirs from the same thread would name it twice.
   */
  private sources(): Post[] {
    const seen = new Set<string>();
    const sources: Post[] = [];

    for (const source of this.attrs.post.referenceSources() || []) {
      const discussion = source && source.discussion();

      if (!source || !discussion || seen.has(String(discussion.id()))) continue;

      seen.add(String(discussion.id()));
      sources.push(source);
    }

    return sources;
  }
}
