/**
 * A title carrying the shape this syntax ends with would cut the regex short
 * on the next parse. Mirrors `UnparseDiscussionReferences::sanitiseTitle()`,
 * and mentions' own `getCleanDisplayName` for the same reason.
 */
export default function cleanTitle(title: string): string {
  return title.replace(/"#[a-z]{0,3}[0-9]+/, '_');
}
