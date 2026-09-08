import EventPost from 'flarum/forum/components/EventPost';
export default class ReferencedEventPost extends EventPost {
    icon(): string;
    descriptionKey(): string;
}
