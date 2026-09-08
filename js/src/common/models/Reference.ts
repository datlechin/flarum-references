import Model from 'flarum/common/Model';
import type Discussion from 'flarum/common/models/Discussion';
import type Post from 'flarum/common/models/Post';
import type User from 'flarum/common/models/User';

export default class Reference extends Model {
  relationType() {
    return Model.attribute<string>('relationType').call(this);
  }

  note() {
    return Model.attribute<string | null>('note').call(this);
  }

  origin() {
    return Model.attribute<string>('origin').call(this);
  }

  targetType() {
    return Model.attribute<string>('targetType').call(this);
  }

  targetId() {
    return Model.attribute<number>('targetId').call(this);
  }

  broken() {
    return Model.attribute<boolean>('broken').call(this);
  }

  createdAt() {
    return Model.attribute<Date | undefined, string | undefined>('createdAt', Model.transformDate).call(this);
  }

  sourcePost() {
    return Model.hasOne<Post | null>('sourcePost').call(this);
  }

  sourceDiscussion() {
    return Model.hasOne<Discussion>('sourceDiscussion').call(this);
  }

  targetDiscussion() {
    return Model.hasOne<Discussion | null>('targetDiscussion').call(this);
  }

  createdBy() {
    return Model.hasOne<User | null>('createdBy').call(this);
  }

  target() {
    return Model.hasOne<Discussion | Post | null>('target').call(this);
  }
}
