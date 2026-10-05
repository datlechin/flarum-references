# Changelog

## 1.1.0

Run `php flarum migrate` after updating. The migration removes references recorded within a single discussion and recalculates the reference counts.

### Fixed

- Replies within a discussion were recorded as references. Links within the same discussion are no longer recorded.
- The post footer hid the Mentions "replied to this" list.
- Links inside quotes were recorded as references.
- A discussion that linked another several times was counted several times. Counts now use distinct discussions.
- The sidebar count did not match the list, and the full list showed duplicate titles.
- Followers received the author's notification ("referenced your post").
- Notification types were missing from the notification settings.
- A post that both mentioned and linked a post sent two notifications.
- Event posts from different users were merged under the first user.
- Hidden and unapproved posts affected counts, the graph and related discussions.
- The graph could show discussions that are only connected through a discussion the user cannot see.
- `references:` and `referenced-by:` did not match links to posts.
- Links to a missing post number, such as `/d/12/99`, were not recorded. They now reference the discussion.
- A discussion could reference itself through a manual reference.
- Related discussions ignored the cache duration setting.
- Analytics showed "Nothing recorded yet" before the first daily rollup.

### Added

- "Manage links" in the discussion moderation menu: add, edit and remove links in one place.
- The add link form searches for discussions instead of asking for an ID.
- Event posts link to the discussion the reference came from.
- A separate notification type for followers.
- `filter[incoming]` and `filter[outgoing]` on `/api/post-references`.

### Changed

- New discussion sidebar: short "Linked from" and "Links to" lists, titles only, and one "N links" button on phones.
- "View all links" opens one dialog with tabs for both directions and the map. The separate graph button is gone.
- Reader-facing text says "linked" instead of "referenced".
- Admin settings are grouped into Recording, Display, Notifications and Advanced.
- Discussion reference counts and lists are only included on the discussion page, not in discussion lists.
- `references_count` counts distinct discussions, excluding hidden and unapproved content.

## 1.0.0

Initial release.
