import app from 'flarum/admin/app';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';

// Names shared with locale/en.yml and Settings\Config::DEFAULTS. A unit test
// asserts every one of these has both a label and a help string, because the
// settings page prints the raw key when either is missing.
export const PREFIX = 'datlechin-references.';

export const SETTING = {
  enabled: 'enabled',
  eventPostEnabled: 'event_post_enabled',
  extractUrlReferences: 'extract_url_references',
  extractShortReferences: 'extract_short_references',
  extractMentionReferences: 'extract_mention_references',
  maxPreview: 'max_preview',
  relatedDiscussionsEnabled: 'related_discussions_enabled',
  relatedDiscussionsLimit: 'related_discussions_limit',
  relatedMaxCandidates: 'related_max_candidates',
  cacheTtl: 'cache_ttl',
  graphMaxDepth: 'graph_max_depth',
  graphMaxPerHop: 'graph_max_per_hop',
  notifyFollowers: 'notify_followers',
  brokenRetentionDays: 'broken_retention_days',
} as const;

// Must match the clamping the server applies on every read, or a value saved
// here comes back as a different one.
export const NUMBER_BOUNDS: Record<string, { min: number; fallback: number }> = {
  [SETTING.maxPreview]: { min: 1, fallback: 4 },
  [SETTING.relatedDiscussionsLimit]: { min: 1, fallback: 5 },
  [SETTING.relatedMaxCandidates]: { min: 1, fallback: 500 },
  [SETTING.cacheTtl]: { min: 0, fallback: 300 },
  [SETTING.graphMaxDepth]: { min: 1, fallback: 2 },
  [SETTING.graphMaxPerHop]: { min: 1, fallback: 50 },
  [SETTING.brokenRetentionDays]: { min: 0, fallback: 365 },
};

export function settingKey(name: string): string {
  return PREFIX + name;
}

export function trans(key: string, params: Record<string, unknown> = {}): Mithril.Children {
  return app.translator.trans(`datlechin-references.admin.${key}`, params);
}

export function transText(key: string, params: Record<string, unknown> = {}): string {
  return extractText(trans(key, params));
}
