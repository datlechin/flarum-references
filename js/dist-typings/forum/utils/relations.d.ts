import type Mithril from 'mithril';
import type Reference from '../../common/models/Reference';
/**
 * The types a moderator may give a link, read from the forum payload so the
 * form cannot offer one the server would refuse.
 */
export declare function relationOptions(): Record<string, string>;
/**
 * A plain link needs no label; only a type a moderator chose gets one.
 */
export declare function relationBadge(reference: Reference): Mithril.Children;
