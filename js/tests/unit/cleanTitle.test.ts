import cleanTitle from '../../src/forum/utils/cleanTitle';

/**
 * The browser half of `CleansATitleThatWouldBreakTheSyntaxTest`.
 *
 * The composer inserts `@"Title"#d123` and the server parses it, so the two
 * have to agree about which titles are safe to put between the quotes. Nothing
 * enforces that agreement, which is why the same cases are written twice.
 */
describe('cleanTitle', () => {
  const cases: [string, string, string][] = [
    ['an ordinary title is untouched', 'Seasoning a cast iron pan', 'Seasoning a cast iron pan'],
    ['a quote on its own is fine', 'The "best" way to season a pan', 'The "best" way to season a pan'],
    ['the discussion shape is replaced', 'Read this"#d12 first', 'Read this_ first'],
    ['the user shape is replaced too', 'Ask them"#42 about it', 'Ask them_ about it'],
    ['the post shape is replaced too', 'See this"#p7 for context', 'See this_ for context'],
    ['a hash with no digits is left alone', 'A title with "#hashtag in it', 'A title with "#hashtag in it'],
  ];

  it.each(cases)('%s', (_name, title, expected) => {
    expect(cleanTitle(title)).toBe(expected);
  });
});
