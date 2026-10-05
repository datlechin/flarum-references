import type Reference from '../common/models/Reference';

declare module 'flarum/common/models/Post' {
  export default interface Post {
    referencedBy: () => false | (Reference | undefined)[];
    referencedByCount: () => number;
    referenceSources: () => false | (Post | undefined)[];
  }
}

declare module 'flarum/common/models/Discussion' {
  export default interface Discussion {
    referencedBy: () => false | (Reference | undefined)[];
    referencedByCount: () => number;
    outgoingReferencesCount: () => number;
    canManageReferences: () => boolean;
    outgoingReferences: () => false | (Reference | undefined)[];
  }
}
