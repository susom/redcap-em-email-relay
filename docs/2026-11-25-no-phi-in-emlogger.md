# Stop logging relayed emails and tokens to em_logger (2026-11-25)

## Problem

- **`service.php` (every relay call).** It called `emLog("Incoming Request", $_REQUEST)`, which ships on every call because emLog is not debug-gated. The request holds the recipients (to/cc/bcc), subject, body, record_id and the project's `email_token`. Relayed emails usually go to participants.
- **Debug lines.** These logged the project token (on enable, and on every project send) and all system tokens.

## Fix

`service.php` now emDebugs only that a request arrived and for which project. The token values are dropped from the three debug lines; the system-token line logs a count.

## How it was reproduced and verified (local, REDCap 17.5.9, throwaway PID 272)

The module's no-auth service was POSTed with the project token, as an external system calls it. The test recipient is `@example.org`, and local mail goes to a MailHog sink.

| Code | Log lines with recipient / subject / body / token | Delivered |
| --- | --- | --- |
| `master` | 1 (INFO) | yes |
| this branch, debug off | 0 | yes |
| this branch, project debug on | 0 of 4 | yes |

Scripts: `emlogger-phi-review/e2e/email-relay.sh` and `em-enable.php` in the REDCap EM workspace.

## Not changed: a separate existing issue

When a project has never saved the module's config, `getProjectSetting('project-ip')` returns `null`. On PHP 8 that makes `array_filter(null)` in `sendProjectEmail()` fatal. It surfaced on a fresh local enable; once the config is saved, the setting is an array. Worth a `(array)` cast separately.
