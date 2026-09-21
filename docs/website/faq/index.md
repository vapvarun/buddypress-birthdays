# Frequently Asked Questions

## Where do birthdays come from?

From a BuddyPress Extended Profile field of type Date Selector (datebox) or Birthdate. Create one at Users > Profile Fields and select it in the widget or shortcode. If you do not pick one in the shortcode, the plugin uses the first date field it finds.

## Can I show birthdays in a page or post?

Yes. Use the `[bp_birthdays]` shortcode anywhere in your content. See the shortcode reference for all attributes.

## Where is the admin settings page?

Under WB Plugins > Birthdays. The plugin no longer uses a Settings menu entry.

## Can members hide their birthday?

Yes. Every member can tick "Hide my birthday everywhere on this site" on their BuddyPress Settings > General screen. When they do, they are removed from the widget, the shortcode, activity posts, notifications, and emails. Developers can force the same result with the `bb_birthday_user_opted_out` filter.

## What is the difference between Friends and Followings?

Friends shows the viewer's BuddyPress friends and needs the Friends component active. Followings shows the people the viewer follows and needs the third-party BP Follow plugin. If a component or plugin is not active, its option does not appear. Both modes need a logged-in viewer.

## Can members send birthday wishes?

Yes, when the BuddyPress Messages component is active and the viewer is logged in. The button opens the private message compose screen with the birthday member preselected. Logged-out visitors do not see it.

## Does it send emails or notifications automatically after I activate it?

No. Birthday emails, activity posts, BuddyPress notifications, and the admin summary are all off until you enable them under WB Plugins > Birthdays.

## Does it cache birthday data?

Yes, in the WordPress object cache. The lifetime is set by the Cache Duration setting (default 30 minutes) and is cleared automatically on profile, friendship, follow, and user changes, and daily by cron.

## Can I customize the date format?

Yes. Use a PHP date format such as `F d` for "January 15", `M j` for "Jan 15", or `j M` for "15 Jan", in the widget settings or the shortcode's `date_format` attribute.

## Does it respect privacy settings?

Yes. It respects the BuddyPress visibility level on the birthday field, so a private field is not shown to unauthorized viewers, and it honours each member's own opt-out.

## Will it slow down a large community?

No. Every widget mode fetches a bounded, batch-primed candidate pool instead of walking every member. Tune the bounds with the `bb_birthdays_widget_candidate_multiplier` and `bb_birthdays_widget_candidate_cap` filters.

## Does it work with BuddyBoss and Youzify?

Yes. It works with BuddyBoss Platform, the BuddyBoss theme, and Youzify, and is tested with the BuddyX and Reign themes.

## Does it add REST endpoints or a block?

No. The plugin ships no REST API endpoints and no Gutenberg blocks. Use the widget (including the Legacy Widget block) or the shortcode.
