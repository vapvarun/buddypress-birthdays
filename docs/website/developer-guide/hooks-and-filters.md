# Hooks and Filters

The plugin fires filters only. It registers no custom action hooks. All names below are verified against the shipped code.

## Display and assets

### bb_core_load_assets

Last-resort override to force the front-end CSS and JS to load when none of the built-in context checks matched.

```php
add_filter( 'bb_core_load_assets', '__return_true' );
```

### bb_birthdays_animation_speed

Front-end animation speed in milliseconds. Default 300. Passed to the browser only.

### bb_birthdays_tooltip_delay

Front-end tooltip delay in milliseconds. Default 300. Passed to the browser only.

### bb_birthdays_cache_duration

Value localized to the front-end script as `bbBirthdays.settings.cache_duration`. Default 1800 (seconds). This is JS-facing only; it does not set the server-side widget cache lifetime. The server TTL comes from the Cache Duration setting.

### bbirthdays_get_name_to_display

The final display name for a birthday member. The return value is escaped with `esc_html()` after the filter, so it cannot inject markup.

```php
add_filter( 'bbirthdays_get_name_to_display', function ( $display, $user_info ) {
    return $display;
}, 10, 2 );
```

## Scale and performance

### bb_birthdays_widget_candidate_multiplier

Multiplier applied to the display count to size the SQL candidate pool (pool = display count x multiplier), before the cap. Default 4. Clamped to a minimum of 1. Receives the widget instance as a second argument.

### bb_birthdays_widget_candidate_cap

Absolute hard cap (the SQL LIMIT) on the widget candidate pool. Default 200. Clamped to a minimum of 1. Receives the widget instance as a second argument.

### bb_birthdays_notification_recipient_limit

Cap on how many recipients a single birthday notification fans out to when Notify Friends Only is off. Default 500. Use 0 or -1 for unbounded. Truncation is logged. Receives the birthday user ID as a second argument.

## Privacy

### bb_birthday_user_opted_out

Whether a member's birthday is hidden from every plugin surface. Return true to hide, false to always show. The default reads the `bb_birthday_hidden` user meta.

```php
add_filter( 'bb_birthday_user_opted_out', function ( $opted_out, $user_id ) {
    return $opted_out;
}, 10, 2 );
```

## Admin

### bp_birthdays_admin_capability

Capability required to view the admin menu and settings page. Default `manage_options`.

### bp_birthdays_admin_tabs

Add or alter the admin settings tabs. The Discover tab is appended after this filter, so it cannot be removed through it.

### wbcom_hub_wrapper_helper_slugs

Shared across Wbcom plugins. Lists the legacy wbcom-wrapper helper page slugs that must not be rendered as plugin cards on the shared WB Plugins hub landing.

## A note on extensibility

The per-item widget markup (avatar, age, zodiac, wish button) is not currently wrapped in a filter, and neither is the assembled birthday list. Altering that markup means overriding the CSS or forking the widget. This applies to the shortcode identically, since it renders through the same widget code.
