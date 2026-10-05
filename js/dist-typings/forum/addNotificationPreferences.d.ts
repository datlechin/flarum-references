/**
 * A type the grid does not list has no row in the notification settings, so
 * nobody could turn these off or ask for them by email.
 *
 * Extended by module path, not by import: the grid is a lazily loaded chunk in
 * Flarum 2, so an imported `NotificationGrid` is undefined when the
 * initializer runs. Reading its prototype threw, and every step of the
 * initializer after this one, the `#` discussion picker among them, silently
 * never ran.
 */
export default function addNotificationPreferences(): void;
