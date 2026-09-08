import type Mithril from 'mithril';
export declare const PREFIX = "datlechin-references.";
export declare const SETTING: {
    readonly enabled: "enabled";
    readonly eventPostEnabled: "event_post_enabled";
    readonly extractUrlReferences: "extract_url_references";
    readonly extractShortReferences: "extract_short_references";
    readonly extractMentionReferences: "extract_mention_references";
    readonly maxPreview: "max_preview";
    readonly relatedDiscussionsEnabled: "related_discussions_enabled";
    readonly relatedDiscussionsLimit: "related_discussions_limit";
    readonly relatedMaxCandidates: "related_max_candidates";
    readonly cacheTtl: "cache_ttl";
    readonly graphMaxDepth: "graph_max_depth";
    readonly graphMaxPerHop: "graph_max_per_hop";
    readonly notifyFollowers: "notify_followers";
    readonly brokenRetentionDays: "broken_retention_days";
};
export declare const NUMBER_BOUNDS: Record<string, {
    min: number;
    fallback: number;
}>;
export declare function settingKey(name: string): string;
export declare function trans(key: string, params?: Record<string, unknown>): Mithril.Children;
export declare function transText(key: string, params?: Record<string, unknown>): string;
