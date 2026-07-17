# wppqa Baseline — buddypress-birthdays @ 2.5.0

Date: 2026-06-05
Source commit: 3f530d4341fab4132715e802b716bbd033443bd9
Run BEFORE manifest generation (hard rule #6).

## Per-check results

| Check | Passed | Failed | Skipped | Verdict |
|---|---|---|---|---|
| `wppqa_check_plugin_dev_rules` | 9 | 0 | 0 | PASS (4 low warnings) |
| `wppqa_check_rest_js_contract` | 0 | 0 | 1 | SKIPPED — plugin registers no REST routes |
| `wppqa_check_wiring_completeness` | 0 | 0 | 1 | SKIPPED — no `templates/` dir; settings read by widget/cron service layer, not templates |

**Release-readiness gate:** `failed == 0` across all checks. No blocking issues.

## plugin-dev-rules findings (all low severity)

| ID | Severity | Finding | File:line |
|---|---|---|---|
| PLUGIN-DEV-RULES-001 | low | Button height 32px < 40px tap target | assets/css/bb-core.css:435 |
| PLUGIN-DEV-RULES-002 | low | Button height 28px < 40px tap target | assets/css/bb-core.css:485 |
| PLUGIN-DEV-RULES-003 | low | Button height 32px < 40px tap target | assets/css/bb-core.min.css:1 |
| PLUGIN-DEV-RULES-004 | low | Button height 28px < 40px tap target | assets/css/bb-core.min.css:1 |

003/004 are the minified mirror of 001/002 — same two source rules. Two distinct issues: the
send-wishes action button + pagination buttons in the widget are below the 40px touch target
(frontend-responsive Rule 4). Cosmetic/a11y, not functional.

## Pre-triaged FALSE POSITIVES (per environment quirk)

- The `wppqa_check_plugin_dev_rules` nonce-no-cap heuristic does not recognise `nopriv` AJAX
  actions. `bb_birthdays_action` is a deliberate public (nopriv) endpoint whose sub-actions
  self-gate (`refresh_widget` -> is_user_logged_in()+current_user_can('read'); `mark_wished`
  -> get_current_user_id()). If a future wppqa run flags this as 'nonce-no-cap', treat it as a
  FALSE POSITIVE — the nonce IS verified (bb_birthdays_nonce) and capability gating is per-branch.
  (This run produced no such flag.)

## Not caught by wppqa but surfaced during manifest derivation (see manifest static_analysis.notes)

- BB-2/BB-3 (medium): big-site scale risks — unindexed DATE_FORMAT() cron scan + O(members)
  N+1 widget render on the 'all members' path with no SQL LIMIT.
- BB-1 (informational): widget echoes register_sidebar wrappers with EscapeOutput suppressed (trusted).
- BB-4 (low): BP-notification fan-out silently capped at 500 recipients.
