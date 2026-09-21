# Introduction

Birthday Widget for BuddyPress lists your community members' upcoming birthdays wherever you want them: a sidebar widget, a block area, or any page or post through the `[bp_birthdays]` shortcode. Today's birthdays are highlighted, an optional age is shown, and logged-in members can open a private message to send wishes in one click.

Birthdays are read from a BuddyPress Extended Profile date field, so the plugin needs BuddyPress active with at least one date profile field. It respects each field's BuddyPress visibility level, and every member can hide their own birthday from every surface of the plugin.

## What it does

- Shows upcoming birthdays for all members, friends, or the people a member follows.
- Highlights today's birthdays and can show the age each member is turning.
- Sends birthday greeting emails, posts to the activity feed, and raises BuddyPress notifications on the day (all off by default).
- Sends the site owner a daily summary of the day's birthdays.
- Lets each member opt out of having their birthday shown anywhere (GDPR).
- Paginates long lists and bounds every query so large communities stay fast.

## Requirements

- WordPress 6.5 or higher (tested up to 7.0).
- PHP 8.0 or higher.
- BuddyPress active, with at least one Extended Profile field of type Date Selector (datebox) or Birthdate.

Optional components extend the plugin when present:

- BuddyPress Messages: enables the send-wishes button.
- BuddyPress Friends: enables the Friends display mode and friends-only notifications.
- BuddyPress Activity and Notifications: enable the activity auto-post and in-app notifications.
- BP Follow (third-party): enables the Followings display mode.

The plugin also works with BuddyBoss Platform and the BuddyBoss theme, and with Youzify. It is tested with the BuddyX and Reign themes.

## Where things live

- Front end: the widget, the block widget, and the `[bp_birthdays]` shortcode.
- Admin: WB Plugins > Birthdays (overview dashboard plus grouped settings tabs).
- Member self-service: the "Hide my birthday" checkbox on each member's BuddyPress Settings > General screen.
