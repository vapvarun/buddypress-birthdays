# Privacy and GDPR

A member's birthday is personal data. The plugin gives members two independent ways to control it, and respects both on every surface.

## Per-member opt-out

Every member has a "Hide my birthday everywhere on this site" checkbox on their BuddyPress Settings > General screen. When a member ticks it, they are removed from:

- the widget and the shortcode,
- the activity feed auto-posts,
- BuddyPress notifications,
- birthday greeting emails,
- the admin summary email.

The choice is stored as user meta and enforced through a single central check, so an opted-out member never appears anywhere the plugin renders or messages.

Note: this opt-out is intentionally not removed when the plugin is uninstalled, so a member's privacy choice survives a plugin removal and reinstall.

## Programmatic control

Developers can force the same result without touching stored user meta:

```php
add_filter( 'bb_birthday_user_opted_out', function ( $opted_out, $user_id ) {
    // Return true to hide this member's birthday everywhere.
    return $opted_out;
}, 10, 2 );
```

This is useful for wiring the opt-out to a site-wide consent store or a privacy plugin.

## BuddyPress field visibility

Separately from the opt-out, the plugin honours the visibility level set on the birthday profile field:

- Public: shown to everyone.
- Logged-in members: shown only to logged-in viewers.
- Friends: shown only to the member's friends (needs the Friends component).
- Only Me: never shown in the widget, shortcode, or notifications.

Because both controls are respected, a private birthday field stays private even if a member has not used the opt-out.
