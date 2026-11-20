# Notifications and Emails

On each day, a WordPress cron event finds the members whose birthday is today and, according to your settings, sends greeting emails, posts to the activity feed, raises BuddyPress notifications, and emails the site owner a summary. A per-day guard makes sure each member is processed only once per day. Members who have opted out are skipped everywhere.

All of these are off by default. Enable them under WB Plugins > Birthdays.

## Send time

The daily check runs at the time set by the Email tab's Send Time (default 09:00, in 24-hour HH:MM). The cron re-schedules itself to that time and self-heals if the event is ever lost.

If a send fails, that day is retried every hour until the end of the next day, and only the channels that failed are sent again. A daily check that runs late, after midnight but before the Send Time, still processes the day it was scheduled for. Members born on February 29 are celebrated on February 28 in years without a February 29.

## Birthday greeting email

When Birthday Emails are enabled, each member receives a greeting on their birthday. The email is a BuddyPress email of type `birthday-greeting`, so you can edit its subject and body under BuddyPress > Emails.

The greeting uses standard BuddyPress email tokens:

- `{{{site.name}}}` - your site name.
- `{{{recipient.name}}}` - the member's name.
- `{{{birthday.age}}}` - the age the member is turning.
- `{{{recipient.url}}}` - a link to the member's profile.

If BuddyPress cannot send the email, the plugin falls back to a plain `wp_mail` send. A send that still fails is written to the PHP error log and retried an hour later.

## Activity feed post

When Activity Posts are enabled, a birthday announcement is posted to the activity feed. Set the template on the Activity tab using these placeholders:

- `{name}` - the member's name, linked to their profile.
- `{age}` - the age the member is turning.
- `{profile_url}` - the member's profile URL.

Default template: `Today is {name}'s birthday! Send your wishes!`

## BuddyPress notification

When Notifications are enabled, members receive an in-app BuddyPress notification. Turn on Notify Friends Only to restrict it to the birthday member's friends. Notify Friends Only needs the BuddyPress Friends component; while that component is off, no birthday notifications are sent and the Notifications tab shows a warning. Members who hid their birthday receive no birthday notifications. Set the text on the Notifications tab using the `{name}` placeholder.

Default text: `It's {name}'s birthday today!`

When Notify Friends Only is off, every member who may see the birthday is notified. On large sites the notifications are written in chunks of 500 members, one scheduled action per chunk, so a birthday can take a few minutes to reach everyone. Change the chunk size with the `bb_birthdays_notification_recipient_limit` filter.

## Admin summary email

When the Daily Summary Email is enabled, the recipient (the Summary Recipient setting, or the site admin email when empty) receives one list of the day's birthdays per day. It is a BuddyPress email of type `birthday-admin-summary`, also editable under BuddyPress > Emails, with a `wp_mail` fallback. It opens with its own "Hi Admin,", so BuddyPress does not add its usual "Hi name," line.

## Emails not appearing under BuddyPress > Emails

The two email templates are created automatically in the admin, and again after BuddyPress > Tools > Reinstall emails. If one could not be created, the plugin tries again on the next admin page load. If you do not see them, load any admin page while the plugin is active. See Troubleshooting if they are still missing.
