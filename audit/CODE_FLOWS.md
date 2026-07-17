# Code Flows — Birthday Widget for BuddyPress (2.4.1)

## Flow A — Widget render (frontend)
```
Sidebar/[bp_birthdays] → Widget_Buddypress_Birthdays::widget($args,$instance)
  → guard: instance['birthday_field_name'] set + bp_is_active() exists
  → friends/followers filters require is_user_logged_in()
  → wp_cache_get(md5(json(instance)) [+ _user_<id> for friends/followers], 'bp_birthdays')
      miss → bbirthdays_get_array($instance)
              → resolve member set (friends_get_friend_user_ids | bp_follow_get_following | all-members SQL on bp_xprofile_data)
              → per member: get_user_birthday_data (BP_XProfile_ProfileData::get_value_byid → direct $wpdb fallback)
                            → xprofile_get_field_visibility_level gate (onlyme/adminsonly/loggedin/friends/public)
                            → clean_birthday_string → bbirthday_get_upcoming_birthday (leap-year aware)
                            → uasort: today first, then next-occurrence chronological
              → wp_cache_set(..., 30 * MINUTE_IN_SECONDS)
  → empty → return (no markup)
  → paginate (bbirthday_page query arg) → echo per-item: avatar, name (display_name_type), Turning N / Today!, emoji, zodiac (if zodiac_enabled), send-wishes link (messages component + logged-in)
```
Key files: assets/inc/buddypress-birthdays-widget.php · includes/class-bp-birthdays-helpers.php (zodiac/age/format)
Settings consumed: bp_birthdays_settings.zodiac_enabled. Scale risk: all-members path is O(members), no SQL LIMIT (manifest BB-3).

## Flow B — Admin settings save
```
wp-admin → Birthday Settings (BP menu or Settings fallback) → BP_Birthdays_Admin::render_settings_page
  → tab nav (general/email/activity/notifications/display) → form action=options.php, settings_fields('bp_birthdays_settings_group')
  → Settings API → sanitize_settings() (merges with existing option, per-key sanitize) → update option bp_birthdays_settings
```
Key file: admin/class-bp-birthdays-admin.php. Cap: manage_options.

## Flow C — Daily notifications cron
```
bp_birthdays_daily_check (scheduled at email_send_time, site tz)
  → BP_Birthdays_Notifications::process_daily_birthdays
      → get_todays_birthdays(): $wpdb join bp_xprofile_data×users WHERE DATE_FORMAT(value,'%m-%d')=today  [non-sargable scan — BB-2]
      → per user not already in bp_birthdays_sent_today:
          email_enabled        → send_birthday_email (bp_send_email type birthday-greeting; wp_mail fallback)
          activity_enabled      → post_birthday_activity (bp_activity_add component 'birthdays')
          notification_enabled  → send_bp_notifications (friends-only or get_users number=500 — BB-4)
      → admin_email_enabled    → send_admin_summary (type birthday-admin-summary)
```
Tracking options: bp_birthdays_sent_today (per-day dedupe), bp_birthdays_last_check_date (rollover reset).

## Flow D — Cache invalidation
```
xprofile_data_after_save | friends_friendship_* | delete_user/wpmu_delete_user/user_register
  | bp_follow_start/stop_following | bb_cleanup_old_wishes(daily)
    → bb_clear_birthday_caches() → wp_cache_flush_group('bp_birthdays')
Widget::update() also flushes group + deletes old instance key on settings change.
```

## Flow E — Asset loading
```
wp_enqueue_scripts → bb_register_core_css/js → bb_should_load_assets() (active widget, admin, shortcode, BP pages, BuddyBoss/Youzify, common templates, bb_core_load_assets filter)
wp_footer@5 → bb_footer_asset_check (late widget/shortcode fallback)
JS localized as bbBirthdays { ajaxurl, nonce(bb_birthdays_nonce), settings.confetti_enabled, filtered animation/tooltip/cache }
```

## Roles
Admin: configure settings (manage_options). Logged-in members: see friends/followers filters, send wishes, mark_wished/refresh AJAX. Logged-out: "All Members" public widget only.
