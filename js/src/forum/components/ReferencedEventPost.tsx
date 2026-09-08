import EventPost from 'flarum/forum/components/EventPost';

export default class ReferencedEventPost extends EventPost {
  icon(): string {
    return 'fas fa-link';
  }

  descriptionKey(): string {
    return 'datlechin-references.forum.event_post.referenced';
  }
}
