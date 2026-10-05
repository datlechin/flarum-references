/**
 * "Linked from <discussion>, <discussion>" under a post another discussion
 * links to.
 *
 * Beside Mentions' "replied to this", never instead of it. Replies are a
 * conversation inside the discussion and Mentions owns them; removing its list
 * to draw ours put every reply under the post as the discussion referencing
 * itself. The server leaves post mentions out of this list for the same
 * reason, so the two never name the same post.
 */
export default function addBacklinkFooterItem(): void;
