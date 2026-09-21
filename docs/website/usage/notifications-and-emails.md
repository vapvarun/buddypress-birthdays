# Notifications and Emails

On each day, a WordPress cron event finds the members whose birthday is today and, according to your settings, sends greeting emails, posts to the activity feed, raises BuddyPress notifications, and emails the site owner a summary. A per-day guard makes sure each member is processed only once per day. Members who have opted out are skipped everywhere.

All of these are off by default. Enable them under WB Plugins > Birthdays.

## Send time

The daily check runs at the time set by the Email tab's Send Time (default 09:00, in 24-hour HH:MM). The cron re-schedules itself to that time and self-heals if the event is ever lost.

## Birthday greeting email

When Birthday Emails are enabled, each member receives a greeting on their birthday. The email is a BuddyPress email of type `birthday-greeting`, so you can edit its subject and body under BuddyPress > Emails.

The greeting uses standard BuddyPress email tokens:

- `{{{site.name}}}` - your site name.
- `{{{recipient.name}}}` - the member's name.
- `{{{birthday.age}}}` - the age the member is turning.
- `{{{recipient.url}}}` - a link to the member's profile.

If BuddyPress email delivery is unavailable, the plugin falls back to a plain `wp_mail` send.

## Activity feed post

When Activity Posts are enabled, a birthday announcement is posted to the activity feed. Set the template on the Activity tab using these placeholders:

- `{name}` - the member's name, linked to their profile.
- `{age}` - the age the member is turning.
- `{profile_url}` - the member's profile URL.

Default template: `Today is {name}'s birthday! Send your wishes!`

## BuddyPress notification

When Notifications are enabled, members receive an in-app BuddyPress notification. Turn on Notify Friends Only to restrict it to the birthday member's friends. Set the text on the Notifications tab using the `{name}` placeholder.

Default text: `It's {name}'s birthday today!`

When Notify Friends Only is off, the number of recipients per birthday is capped (default 500) to protect large sites. Adjust or remove the cap with the `bb_birthdays_notification_recipient_limit` filter.

## Admin summary email

When the Admin Summary Email is enabled, the recipient (the Admin Email setting, or the site admin email when empty) receives a daily list of the day's birthdays. It is a BuddyPress email of type `birthday-admin-summary`, also editable under BuddyPress > Emails, with a `wp_mail` fallback.

## Emails not appearing under BuddyPress > Emails

The two email templates are created once, automatically, in the admin. If you do not see them, load any admin page while the plugin is active. See Troubleshooting if they are still missing.
