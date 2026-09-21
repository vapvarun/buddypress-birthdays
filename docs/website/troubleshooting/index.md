# Troubleshooting

## No birthdays are showing

- Confirm a date profile field exists at Users > Profile Fields, of type Date Selector (datebox) or Birthdate.
- Confirm the widget or shortcode points at that field. In the shortcode, `field_name` is the numeric field ID, not the field label.
- Confirm members have actually filled in their birthday.
- Check the range: try No Limit while testing.
- Check the display mode: Friends and Followings show nothing to logged-out visitors, and nothing if the viewer has no connections.
- Members whose birthday field visibility is "Only Me", and members who opted out on Settings > General, are excluded by design.
- The current viewer's own birthday is never shown to themselves.

## The wrong date is showing

- Check Settings > General > Timezone matches your community's location. Birthdays are calculated in the site timezone.
- Make sure the widget's Date Format matches how you want the date displayed; it does not change which birthdays match.
- Save the widget (or wait for the cache to expire) after changing profile data, so the cached list refreshes.

## Changes are not appearing

- The list is cached for the Cache Duration (default 30 minutes). Save the widget settings to clear the cache immediately, or lower the Cache Duration while testing.
- Profile, friendship, follow, and user changes clear the cache automatically; a manual profile edit that does not fire those hooks may need a widget save.

## The send-wishes button is missing

- It only appears when the BuddyPress Messages component is active, the widget's "Enable option to wish them" is on, and the viewer is logged in. Logged-out visitors never see it.

## The Friends or Followings option is missing from the widget

- Friends appears only when the BuddyPress Friends component is active.
- Followings appears only when the BP Follow plugin (or BuddyBoss activity follow) is active.

## Emails are not being sent

- Confirm Birthday Emails are enabled on the Email tab.
- Confirm WordPress cron is running. The daily check runs at the configured Send Time.
- Look for the "birthday-greeting" email under BuddyPress > Emails and confirm its content is set.
- Test your site's outgoing email separately (an SMTP plugin often helps on hosts that block `wp_mail`).

## The email templates are missing under BuddyPress > Emails

- The two templates ("birthday-greeting" and "birthday-admin-summary") are created once, automatically, when an admin page loads while the plugin is active. Load any wp-admin page and check again.

## Activity posts or notifications are not appearing

- Confirm the relevant BuddyPress component (Activity or Notifications) is active.
- Confirm the matching option is enabled on the Activity or Notifications tab.
- For notifications with Notify Friends Only off, recipients are capped (default 500). Raise or remove the cap with the `bb_birthdays_notification_recipient_limit` filter if you expect more.

## Front-end styles or scripts look stale after an update

- Front-end assets are versioned with the plugin version, so a hard refresh or a cache purge (browser and any caching plugin or CDN) resolves a stale copy after upgrading.
