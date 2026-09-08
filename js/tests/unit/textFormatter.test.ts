import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';

import { filterDiscussionReferences, postFilterDiscussionReferences } from '../../src/forum/utils/textFormatter';

/**
 * The browser twin of `ConfigureDiscussionReferences::addDiscussionTitle()`.
 *
 * The composer preview is parsed here and never reaches the server, so a drift
 * between the two shows up as a preview that differs from the posted result.
 * Nothing enforces that they match; this does.
 */

function tag(attributes: Record<string, string>) {
  const state: Record<string, unknown> = { ...attributes, invalidated: false };

  return {
    state,
    getAttribute: (name: string) => state[name] as string,
    setAttribute: (name: string, value: string | boolean) => {
      state[name] = value;
    },
    invalidate: () => {
      state.invalidated = true;
    },
  };
}

beforeAll(() => {
  bootstrapForum();

  app.store.pushPayload({
    data: {
      id: '12',
      type: 'discussions',
      attributes: { title: 'Seasoning a cast iron pan', slug: '12-seasoning-a-cast-iron-pan' },
    },
  });
});

describe('filterDiscussionReferences', () => {
  it('fills in the title and slug from the store', () => {
    const subject = tag({ id: '12' });

    expect(filterDiscussionReferences(subject)).toBe(true);
    expect(subject.state.title).toBe('Seasoning a cast iron pan');
    expect(subject.state.slug).toBe('12-seasoning-a-cast-iron-pan');
  });

  it('leaves a discussion this page has never loaded as typed', () => {
    const subject = tag({ id: '999', title: 'Whatever was written' });

    expect(filterDiscussionReferences(subject)).toBe(true);
    expect(subject.state.title).toBe('Whatever was written');
    expect(subject.state.invalidated).toBe(false);
  });
});

/**
 * The template branches on `@deleted != 1`, and an absent attribute makes that
 * test false, so a live reference previewed as deleted until the browser set
 * this the way the server always has.
 */
describe('postFilterDiscussionReferences', () => {
  it('marks a reference as not deleted, which the server does from the database', () => {
    const t = tag({ id: '12' });

    postFilterDiscussionReferences(t);

    expect(t.state.deleted).toBe(false);
  });
});
