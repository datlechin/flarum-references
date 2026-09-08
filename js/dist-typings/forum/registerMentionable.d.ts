/**
 * `HashMentionFormat` holds an array of sources and is declared extendable,
 * which is how tag mentions already sit there as one of several. Registering
 * ours beside it costs no dropdown, no keyboard handling and no caret maths.
 *
 * Without flarum/mentions there is no dropdown and the syntax still parses.
 */
export default function registerMentionable(): void;
