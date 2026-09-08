# Changelog

## 1.0.0

First release. Requires Flarum 2.0 and PHP 8.2.

### Recording

- A link to a discussion or a post in this forum is recorded as a reference. Core already labels such a link `#12` at render, but stores nothing, so the other end never learns about it.
- A post mention is recorded too, when Mentions is installed, so a post shows one backlink list rather than two that disagree.
- `@"Discussion title"#d12`, inserted by pressing `#` in the composer. It sits in the slot every mention pattern already reserves, so it cannot collide with a tag or a mention whatever order extensions load in.
- The same target linked twice in one post is one reference.
- Editing the link out of a post removes the reference, unless a moderator has classified or annotated it.
- `references:backfill` for posts written before install, since Flarum releases these events through the API layer alone.

### Showing

- A grouped list of references at the discussion they point at, covering the discussion itself and every post in it.
- A list under a post, replacing the one Mentions adds so the two do not sit side by side saying different things.
- Related discussions from co-citation: which discussions keep getting cited alongside this one.
- A most referenced sort on the discussion list, and a reference graph.
- `references:12`, `referenced-by:12` and `has:references` in search.
- Optional event post in the referenced discussion, off by default, merging consecutive ones.

### Moderation

- Typed relations: duplicate of, see also, supersedes, answers.
- References added by hand between two discussions, without editing anybody's post.
- A reference whose target is deleted is kept and marked, and listed in a broken link report.
- Counts by day, by origin and by relation, with CSV export.
- Three permissions, so reading the report does not carry write access.

### Notifications

- Alert and email to the author of what was referenced, plus followers of that discussion when Subscriptions is installed.
- A post mention Mentions already announced is not announced twice.

### For extension authors

- `ReferenceTargets` extender for new kinds of target. One registration reaches URL extraction, the API's polymorphic `target` relationship, the morph map and the moderation UI.
