import type Reference from '../../common/models/Reference';
/**
 * A plain `references` row says nothing a reader needs, so it shows nothing.
 * Only a moderator's classification earns a label.
 */
export default function relationLabel(reference: Reference): JSX.Element | null;
