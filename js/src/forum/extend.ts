import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import Post from 'flarum/common/models/Post';

import Reference from '../common/models/Reference';
import DiscussionReferencedNotification from './components/DiscussionReferencedNotification';
import PostReferencedNotification from './components/PostReferencedNotification';
import ReferencedEventPost from './components/ReferencedEventPost';
import ReferencesGambit from './query/ReferencesGambit';
import ReferencedByGambit from './query/ReferencedByGambit';
import HasReferencesGambit from './query/HasReferencesGambit';

export default [
  new Extend.Store().add('post-references', Reference),

  new Extend.Model(Post).attribute<number>('referencedByCount').hasMany<Reference>('referencedBy'),

  new Extend.Model(Discussion)
    .attribute<number>('referencedByCount')
    .attribute<number>('outgoingReferencesCount')
    .attribute<boolean>('canManageReferences')
    .hasMany<Reference>('referencedBy')
    .hasMany<Reference>('outgoingReferences'),

  new Extend.Notification().add('discussionReferenced', DiscussionReferencedNotification).add('postReferenced', PostReferencedNotification),

  new Extend.PostTypes().add('discussionReferenced', ReferencedEventPost),

  new Extend.Search().gambit('discussions', ReferencesGambit).gambit('discussions', ReferencedByGambit).gambit('discussions', HasReferencesGambit),
];
