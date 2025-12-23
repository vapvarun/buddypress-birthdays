# BuddyPress Birthdays - Claude Code Instructions

## Project Overview

**Plugin:** Wbcom Designs - Birthday Widget for BuddyPress
**Version:** 2.3.0
**Type:** BuddyPress Addon
**Purpose:** Display upcoming birthdays of BuddyPress members with a widget and shortcode

---

## Basecamp Integration

### Project Configuration
- **Project ID:** 37557337
- **Project Name:** BuddyPress Birthday Widget
- **Card Table ID:** 7406187157

### Column IDs for Card Management

| Column | ID | Purpose |
|--------|-----|---------|
| Triage | 7406187158 | New items to review |
| Not Now | 7406187161 | Deferred items |
| Scope | 7406473977 | Scoped work items |
| Suggestions | 7406193902 | Feature suggestions |
| Bugs | 7406187162 | Bug reports |
| Ready for Development | 7406187175 | Ready to work on |
| In Development | 7406187165 | Currently being worked on |
| Ready for Testing | 7406187178 | Ready for QA |
| In Testing | 9099765454 | Currently being tested |
| Done | 7406187170 | Completed items |

---

## Architecture Quick Reference

### Core Pattern: Birthday Detection

The plugin detects birthdays by:
1. Querying BuddyPress xProfile fields of type `datebox` or `birthdate`
2. Comparing stored birth dates against current date
3. Calculating "next birthday" for sorting
4. Caching results using WordPress object cache

### Key Files

| File | Purpose | Lines |
|------|---------|-------|
| `buddypress-birthdays.php` | Main plugin file, dependency check | ~60 |
| `core-init.php` | Asset loading, shortcode, AJAX handlers | ~580 |
| `assets/inc/buddypress-birthdays-widget.php` | Widget class, birthday logic | ~920 |
| `assets/css/bb-core.css` | Widget styling | ~340 |
| `assets/js/bb-core.js` | Frontend JavaScript | ~680 |

### Widget Settings

```php
$instance = array(
    'title'                 => 'Upcoming Birthdays',
    'birthday_date_format'  => 'F d',           // Date display format
    'display_age'           => 'yes',           // Show age
    'birthdays_range_limit' => 'no_limit',      // weekly, monthly, no_limit
    'show_birthdays_of'     => 'all',           // all, friends, followers
    'birthdays_to_display'  => 5,               // Number to show
    'birthday_field_name'   => '',              // xProfile field ID
    'emoji'                 => 'balloon',       // cake, balloon, party, none
    'birthday_send_message' => 'yes',           // Show wish button
    'display_name_type'     => 'user_name',     // user_name, nick_name, first_name
);
```

### Shortcode

```php
[bp_birthdays limit="5" show_age="yes" date_format="F d" range_limit="monthly"]
```

### AJAX Actions

| Action | Nonce | Purpose |
|--------|-------|---------|
| `bb_birthdays_action` | bb_birthdays_nonce | Refresh widget, mark wished |

---

## Known Issues Fixed (December 2025)

| Issue | File | Line | Fix |
|-------|------|------|-----|
| Today's birthdays not showing | widget.php | 657 | Compare date strings, not DateTime objects |
| FILTER_SANITIZE_STRING deprecated | core-init.php | 398,403 | Use sanitize_text_field() |
| Static method check always false | widget.php | 484 | Use class_exists() + method_exists() |
| Empty followers array issue | widget.php | 296 | Add array_filter() check |
| Missing AJAX capability check | core-init.php | 407-411 | Add is_user_logged_in() check |
| Missing widget input sanitization | widget.php | 757-766 | Add sanitize_key(), absint() |

---

## Performance Considerations

### Caching
- Uses WordPress object cache (30-minute TTL)
- Cache key includes widget settings + user ID for friends/followers
- Cache invalidated on: xprofile save, friendship change, user registration

### Known N+1 Query Issue
The `bbirthdays_get_array()` method queries database for each user individually. For sites with 200+ users, consider batch fetching xprofile data.

---

## Testing Checklist

- [ ] Widget displays upcoming birthdays correctly
- [ ] Today's birthdays show with "Today!" label
- [ ] Friends/Followers filter works for logged-in users
- [ ] "All Members" works for logged-out users
- [ ] Date format setting applies correctly
- [ ] Age display toggle works
- [ ] Send wishes button opens compose message
- [ ] Cache clears when settings change
- [ ] No PHP 8.2+ deprecation warnings

---

## Third-Party Compatibility

| Plugin | Detection | Notes |
|--------|-----------|-------|
| BuddyPress | `class_exists('BuddyPress')` | Required |
| BuddyBoss | `function_exists('buddyboss_theme')` | Fully compatible |
| BP Follow | `bp_follow_get_following()` | Followers filter |
| Youzify | `function_exists('youzify')` | Compatible |

---

## Key Hooks & Filters

### Filters

```php
// Override animation speed
add_filter( 'bb_birthdays_animation_speed', function( $speed ) {
    return 500; // milliseconds
});

// Override cache duration
add_filter( 'bb_birthdays_cache_duration', function( $duration ) {
    return 3600; // 1 hour
});

// Force asset loading
add_filter( 'bb_core_load_assets', '__return_true' );
```

### Actions

```php
// After xprofile data saved (triggers cache clear)
do_action( 'xprofile_data_after_save', $field_data );
```

---

## Development Commands

```bash
# Run WPCS check
npm run lint:php

# Build assets
npm run build

# Watch for changes
npm run watch
```

---

*Last Updated: December 2025*
