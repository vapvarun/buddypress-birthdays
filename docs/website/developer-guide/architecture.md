# Architecture

A short map of how the plugin is put together, for developers extending or debugging it.

## Data source

The plugin owns no database tables. It reads BuddyPress xProfile data only, and never writes to BuddyPress tables outside BuddyPress APIs. The birthday value comes from an Extended Profile field of type Date Selector (datebox) or Birthdate. The plugin reads:

- `bp_xprofile_data` for member birthday values,
- `bp_xprofile_fields` to discover the date field,
- `bp_xprofile_meta` for the field's stored date format.

The one small piece of plugin-owned data is per-member user meta: `bb_birthday_hidden` (the GDPR opt-out) and `bb_birthday_wished_users` (a short-lived record of who wished whom, pruned after seven days).

## Render surfaces

There is a single render class, `Widget_Buddypress_Birthdays`. Both the widget and the `[bp_birthdays]` shortcode call it, so any render behaviour is identical across the two. The plugin ships no Gutenberg blocks and no REST API endpoints.

## Caching

Rendered lists are stored in the WordPress object cache under the group `bp_birthdays`, keyed by the widget instance (and by viewer for the Friends and Followings modes). The lifetime is the Cache Duration setting (default 30 minutes, clamped to 1 to 1440). The cache is cleared automatically on:

- profile date-field saves (`xprofile_data_after_save`),
- friendship accept, delete, and withdraw,
- follow and unfollow (when BP Follow is active),
- user register and delete,
- widget settings save,
- a member changing their opt-out,
- a daily cron flush.

## Scale bounds

Every widget mode (all members, friends, followers) trims candidates in SQL, ordered by upcoming-birthday proximity, to a bounded pool before the per-member loop runs. The pool size is `display count x bb_birthdays_widget_candidate_multiplier` (default 4), hard-capped by `bb_birthdays_widget_candidate_cap` (default 200). Per-member values are batch-primed in one query, so the render does not run a query per member. Pagination slices the already-fetched, bounded list rather than paging in SQL.

## Cron

Two daily WordPress cron events are used:

- `bp_birthdays_daily_check` runs the birthday processing (emails, activity, notifications, admin summary) at the configured send time.
- `bb_cleanup_old_wishes` prunes the wished-users meta older than seven days and flushes the birthday cache.

Both are scheduled on activation, self-heal if lost, and are cleared on deactivation and uninstall.

## BuddyPress version awareness

The widget branches on `buddypress()->version >= 12.0` to choose between the newer member-URL and slug APIs and their pre-12.0 equivalents, so it works across BuddyPress generations and with BuddyBoss.

## AJAX

A single AJAX action, `bb_birthdays_action`, is registered for both logged-in and logged-out requests and is nonce-verified. It dispatches two sub-actions: `refresh_widget` (clears the birthday cache; requires a logged-in user with the `read` capability) and `mark_wished` (records that the current member wished a birthday member; requires a logged-in actor and a target user ID).
