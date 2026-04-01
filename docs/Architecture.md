# BuddyPress Birthday Widget - Architecture

**Version:** 2.4.1

---

## Overview

BuddyPress Birthday Widget is a WordPress plugin that displays upcoming birthdays of BuddyPress members. It uses a widget-based architecture with shortcode support.

---

## File Structure

```
buddypress-birthdays/
├── buddypress-birthdays.php         # Main plugin file
├── core-init.php                    # Core initialization & hooks
├── readme.txt                       # WordPress.org readme
├── uninstall.php                    # Cleanup on plugin delete
├── docs/
│   ├── documentation.md             # User documentation
│   ├── qa_checklist.md             # QA verification steps
│   └── architecture.md              # This file
├── admin/
│   └── class-bp-birthdays-admin.php # Admin settings page
├── includes/
│   ├── class-bp-birthdays-notifications.php  # Email & notification handling
│   └── class-bp-birthdays-helpers.php        # Helper functions
├── assets/
│   ├── inc/
│   │   └── buddypress-birthdays-widget.php  # Main widget class
│   ├── css/
│   │   └── bb-core.css
│   └── js/
│       └── bb-core.js
└── languages/
    └── buddypress-birthdays.pot
```

---

## Core Components

### 1. Main Plugin File (`buddypress-birthdays.php`)
- Defines constants: `BIRTHDAY_WIDGET_PLUGIN_URL`, `BIRTHDAY_WIDGET_PLUGIN_PATH`
- Loads core files
- Checks BuddyPress dependency
- Adds admin notice for missing BuddyPress

### 2. Core Init (`core-init.php`)
- Defines constants: `BB_CORE_INC`, `BB_CORE_CSS`, `BB_CORE_JS`, `BB_CORE_IMG`, `BB_CORE_VERSION`
- Asset loading (CSS/JS) with smart conditional loading
- Shortcode registration: `[bp_birthdays]`
- AJAX handlers for widget actions
- WP-Cron jobs for daily cleanup
- Cache clearing on various events (friendship, user registration, etc.)
- Debug functionality

### 3. Widget (`buddypress-birthdays-widget.php`)
- Extends `WP_Widget`
- Handles widget form and display
- Birthday query logic
- Date range filtering (weekly/monthly/no_limit)
- Visibility filtering (all/friends/followers)
- Pagination logic
- Birthday data fetching from xProfile
- Age calculation and display
- Zodiac sign display
- Wish button functionality

### 4. Admin Settings (`class-bp-birthdays-admin.php`)
- Singleton pattern
- Settings API registration
- Settings page render (tabbed interface)
- General, Email, Activity, Notifications, Display tabs

### 5. Notifications (`class-bp-birthdays-notifications.php`)
- Singleton pattern
- Email sending via `wp_mail`
- BuddyPress activity posting
- BuddyPress notifications
- WP-Cron scheduling for daily checks
- Manual trigger method (`trigger_now()`)

### 6. Helpers (`class-bp-birthdays-helpers.php`)
- Static methods for:
  - Zodiac sign calculation
  - Age calculation
  - Birthday date checks
  - Days until birthday calculation

---

## Data Flow

### Widget Display Flow

```
1. Widget::widget() called
2. Get widget instance settings
3. Check object cache (wp_cache_get)
4. If no cache:
   a. Get user list based on show_birthdays_of (all/friends/followers)
   b. For 'all': query bp_xprofile_data for users with birthday field
   c. For 'friends': use friends_get_friend_user_ids()
   d. For 'followers': use bp_follow_get_followers()
5. For each user:
   a. Get birthday data from xProfile
   b. Check visibility (xprofile_get_field_visibility_level)
   c. Calculate next birthday date
   d. Filter by date range (weekly/monthly/365 days)
   e. Format display (name, date, age, emoji)
6. Sort by upcoming birthday
7. Apply pagination if enabled
8. Store in object cache (wp_cache_set)
9. Render template
```

### Shortcode Flow

```
1. bb_birthdays_shortcode() called
2. Parse shortcode_atts
3. Create widget instance
4. Map shortcode params to widget instance format
5. Call widget->widget() with ob_start/ob_get_clean
6. Return output
```

---

## Caching Strategy

### Object Cache
- Uses WordPress object cache API (`wp_cache_get`/`wp_cache_set`)
- Cache group: `bp_birthdays`
- Cache key: MD5 hash of widget instance
- Duration: 30 minutes (configurable)

### Cache Invalidation Events
- `xprofile_data_after_save` - profile updated
- `friends_friendship_accepted` - new friendship
- `friends_friendship_deleted` - friendship removed
- `friends_friendship_withdrawn` - friendship withdrawn
- `delete_user` - user deleted
- `wpmu_delete_user` - MS user deleted
- `user_register` - new user registered
- `bp_follow_start_following` - follow started
- `bp_follow_stop_following` - follow stopped
- Settings update
- Daily cron cleanup

---

## Settings Architecture

### Stored in `wp_options`
- Option name: `bp_birthdays_settings`
- Structure:
```php
array(
    'default_field_id' => int,
    'cache_duration' => int,
    'email_enabled' => bool,
    'email_send_time' => string,
    'admin_email_enabled' => bool,
    'admin_email' => string,
    'activity_enabled' => bool,
    'activity_message' => string,
    'notification_enabled' => bool,
    'notification_friends_only' => bool,
    'notification_text' => string,
    'confetti_enabled' => bool,
    'zodiac_enabled' => bool,
)
```

### Default Field Option
- Option name: `bb_birthdays_default_field`
- Stores the xProfile field ID for birthdays

---

## User Roles & Capabilities

The plugin relies on BuddyPress capabilities:
- `bp_moderate` - for admin features
- `friends_create_friendship` - for friends filter
- For viewing: requires birthday field to be set to public visibility

---

## Filters

| Filter | Location | Purpose |
|--------|----------|---------|
| `bb_birthdays_animation_speed` | core-init.php | Customize JS animation speed |
| `bb_birthdays_tooltip_delay` | core-init.php | Customize tooltip delay |
| `bb_birthdays_cache_duration` | core-init.php | Customize cache duration |
| `bb_core_load_assets` | core-init.php | Force asset loading |
| `bbirthdays_get_name_to_display` | widget.php | Customize name display |

---

## Actions

| Action | Location | Purpose |
|--------|----------|---------|
| `bb_cleanup_old_wishes` | core-init.php | Daily cleanup cron |
| `bb_daily_cache_clear` | core-init.php | Daily cache refresh |
| `xprofile_data_after_save` | core-init.php | Clear cache on profile update |
| `friends_friendship_accepted` | core-init.php | Clear cache on new friendship |
| `friends_friendship_deleted` | core-init.php | Clear cache on friendship removal |
| `delete_user` | core-init.php | Clear cache on user delete |
| `user_register` | core-init.php | Clear cache on new user |

---

## Database

### Tables Used (BuddyPress)
- `{$wpdb->prefix}bp_xprofile_data` - stores birthday values
- `{$wpdb->prefix}bp_xprofile_fields` - field definitions
- `{$wpdb->prefix}bp_xprofile_meta` - field metadata (date format)

### Custom Tables
None - uses existing BuddyPress tables

---

## Dependencies

### Required
- WordPress 5.0+
- PHP 7.4+
- BuddyPress 5.0+

### Optional
- BuddyPress Friends component (for friends filter)
- BuddyPress Follow plugin (for followers filter)
- BuddyPress Notifications component (for notifications)

---

## Security

- Nonces verified in AJAX handlers
- Input sanitization (`absint`, `sanitize_text_field`, etc.)
- Output escaping (`esc_html`, `esc_attr`, `esc_url`)
- Capability checks before actions
- SQL prepared with `$wpdb->prepare()`

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 2.4.1 | March 2026 | WPCS fixes, code quality |
| 2.4.0 | Dec 2025 | Admin settings, notifications |
| 2.3.0 | Mid 2025 | Widget fixes, object cache |
| 2.2.0 | Early 2025 | UI redesign |
| 1.0.0 | Initial | Initial release |
