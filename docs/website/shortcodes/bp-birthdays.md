# The [bp_birthdays] Shortcode

Add the `[bp_birthdays]` shortcode to any page, post, or text widget to display the upcoming-birthdays list. It renders through the same code as the widget, so both behave identically.

```text
[bp_birthdays]
```

## Attributes

There are eleven attributes.

| Attribute | What it does | Values | Default |
|---|---|---|---|
| `title` | Heading above the list. | any text | Upcoming Birthdays |
| `limit` | How many birthdays to display in total. | number | 5 |
| `show_age` | Show "Turning X". | `yes` or `no` | yes |
| `show_message_button` | Show the send-wishes button. Needs the Messages component and a logged-in viewer. | `yes` or `no` | yes |
| `date_format` | PHP date format for the displayed date. | any PHP date format | F d |
| `range_limit` | Time window. | `no_limit`, `weekly` (next 7 days), `monthly` (next 30 days) | no_limit |
| `show_birthdays_of` | Which members to show. `friends` needs the Friends component; `followers` needs BP Follow. | `all`, `friends`, `followers` | all |
| `display_name_type` | Which name to show. | `user_name`, `nick_name`, `first_name` | user_name |
| `emoji` | Emoji shown next to each name. | `none`, `cake`, `balloon`, `party` | balloon |
| `field_name` | The numeric ID of the xProfile date field. Despite the name, this expects a number, for example `field_name="12"`. Leave it out and the plugin auto-discovers the first Date Selector or Birthdate field. | numeric field ID | Empty (auto-discover) |
| `birthdays_per_page` | How many birthdays per page in the paginated list. | number | 10 |

## Example

```text
[bp_birthdays limit="20" birthdays_per_page="5" range_limit="monthly" show_age="no"]
```

## Notes

- The confetti animation and zodiac sign are global Display settings, not shortcode attributes. The shortcode reads the same saved options.
- When `field_name` is empty, the handler picks the first Date Selector or Birthdate profile field it finds.
- Assets load automatically wherever the shortcode is used.
