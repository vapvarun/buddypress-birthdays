# Hooks and Filters

The plugin fires filters only. It registers no custom action hooks. All names below are verified against the shipped code.

## Display and assets

### bb_core_load_assets

The widget and the `[bp_birthdays]` shortcode load their CSS and JS when they render, so pages without them load nothing. Return `true` to load the files in the page head anyway, for example when a page builder or cache serves the birthday list.

```php
add_filter( 'bb_core_load_assets', '__return_true' );
```

### bp_birthdays_display_timezone

The timezone birthdays are calculated and shown in. Defaults to the site's timezone. Today, upcoming dates and ages all use it.

```php
add_filter( 'bp_birthdays_display_timezone', function ( $timezone, $user_id ) {
    return $timezone;
}, 10, 2 );
```

### bbirthdays_get_name_to_display

The final display name for a birthday member. The return value is escaped with `esc_html()` after the filter, so it cannot inject markup.

```php
add_filter( 'bbirthdays_get_name_to_display', function ( $display, $user_info ) {
    return $display;
}, 10, 2 );
```

## Scale and performance

### bb_birthdays_widget_candidate_multiplier

Multiplier applied to the display count to size the first SQL candidate batch (batch = display count x multiplier), before the cap. If members in a batch are private or opted out, the widget reads further batches, each twice the size of the last, up to the cap and at most 10 batches. Default 4. Clamped to a minimum of 1. Receives the widget instance as a second argument.

### bb_birthdays_widget_candidate_cap

Absolute hard cap (the SQL LIMIT) on one candidate batch. Default 200. Clamped to a minimum of 1. Receives the widget instance as a second argument.

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

Capability required to open the admin menu and settings page and to save its settings. Default `manage_options`.

### bp_birthdays_admin_tabs

Add or alter the admin settings tabs. The Discover tab is appended after this filter, so it cannot be removed through it.

### wbcom_hub_wrapper_helper_slugs

Shared across Wbcom plugins. Lists the legacy wbcom-wrapper helper page slugs that must not be rendered as plugin cards on the shared WB Plugins hub landing.

## A note on extensibility

The per-item widget markup (avatar, age, zodiac, wish button) is not currently wrapped in a filter, and neither is the assembled birthday list. Altering that markup means overriding the CSS or forking the widget. This applies to the shortcode identically, since it renders through the same widget code.
