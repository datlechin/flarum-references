import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import app from 'flarum/forum/app';

import { filterDiscussionReferences } from '../../src/forum/utils/textFormatter';

/**
 * The browser twin of `ConfigureDiscussionReferences::addDiscussionTitle()`.
 *
 * The composer preview is parsed here and never reaches the server, so a drift
 * between the two shows up as a preview that differs from the posted result.
 * Nothing enforces that they match; this does.
 */

function tag(attributes: Record<string, string>) {
  const state = { ...attributes, invalidated: false };

  return {
    state,
    getAttribute: (name: string) => state[name],
    setAttribute: (name: string, value: string) => {
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
