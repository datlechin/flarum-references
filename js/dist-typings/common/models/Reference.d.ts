import Model from 'flarum/common/Model';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';
export default class Reference extends Model {
    relationType(): string;
    note(): string | null;
    origin(): string;
    targetType(): string;
    targetId(): number;
    broken(): boolean;
    createdAt(): Date | undefined;
    sourcePost(): false | Post | null;
    sourceDiscussion(): false | Discussion;
    targetDiscussion(): false | Discussion | null;
    createdBy(): false | User | null;
    target(): false | Post | Discussion | null;
}
