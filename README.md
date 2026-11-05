# CFS Customer Feedback Survey

Laravel 8 application with an anonymous-by-default five-step survey and an authenticated Admin Dashboard.
The customer form never requires a name or Feedback ID. Email is optional and collected only for an explicitly consented follow-up.

## Local Setup

1. Install PHP dependencies with `composer install`.
2. Configure `.env` with the database connection, `APP_URL`, and `APP_KEY`.
3. Run `php artisan migrate` to apply pending migrations without removing existing records.
4. Run `php artisan cfs:admin:create` to securely create the first Admin account from the terminal. The password is entered privately and must be at least 12 characters.
5. Start the application with `php artisan serve --host=127.0.0.1 --port=8000`.

`APP_TIMEZONE` defaults to `Asia/Manila`. Set it before collecting responses when using another timezone.
There is no public Admin signup and the database seeder does not create an Admin account or demo credentials. `cfs:admin:create` only works while the users table is empty; repeat/public account creation is disabled. After first login, finish Business Profile and verify the recovery email. Keep the Admin email/password private and change the initial password if it was shared during setup.

## Survey and Storage

- The first Home agreement click opens Terms & Conditions as a modal over Home at `/terms?from=home`, without selecting the radio. The footer link opens the standalone `/terms` section. Both use the same shared Terms content and card design. Successful confirmation returns to Home with I Agree automatically selected and Let's Begin enabled. Closing Terms without confirming does not unlock the survey. Confirmation is checked server-side for the current business, policy version, and expiry. The successful response records its consent timestamp and policy version; steps cannot be skipped through direct requests.
- Date/time cannot be in the future. Order type and ordered items are required.
- Service and food ratings use a 1-10 scale and require an explicit selection. Staff assistance supports N/A.
- A reported problem requires a comment. Other comments are optional.
- Validated steps are kept in the server session. Same-tab browser drafts preserve unfinished answers on Back and reload. Home offers Continue Survey and Start Over. A draft expires after 120 minutes without a successfully saved step.
- Review Answers shows a compact editable summary before submission. Changing a saved step requires reviewing it again.
- Optional Follow-up appears at the end of Review Answers. Opting in requires a valid email and separate consent; opting out discards both, even if supplied in a direct request. The email is encrypted in the database and excluded from generic model serialization, notifications and CSV exports. Only the owning admin's Feedback Details displays it, with a mailto link and consent timestamp. Such records are labeled Follow-up requested rather than Anonymous. A follow-up request alone does not mark a positive response as a concern.
- Same-tab drafts retain the follow-up choice while going Back/Next or reloading. Changing the email clears its consent checkbox; unchecking follow-up clears the local email/consent draft. Submission failures retain the selected details for retry. Preserve the existing private `APP_KEY` when migrating or restoring the database: it is needed to decrypt follow-up email addresses.
- A transaction saves feedback, the 16 core answers plus additional questions, submission timestamp, generated `FB-000001` style ID, and Admin notification.
- An internal unique submission token prevents duplicate records on retries.
- New responses are Submitted, or Needs Action when a concern is detected. Admin can mark them In Progress and Resolved. Resolution requires a note; every action records the previous/new status, Admin, note, and timestamp.
- Thank You requires a successful save and shows the generated Feedback ID with animated confetti. Back to Home clears the survey and browser drafts.
- The final submission endpoint is rate-limited. A failed save preserves answers on Review Answers for retry.

## Admin Setup and Monitoring

1. Log in and save Business Profile details. Home and Contact Us remain generic CFS pages without shop-specific branding. Contact Us displays the public `CFS_SUPPORT_EMAIL` and `CFS_SUPPORT_PHONE` settings, separate from private notification details. Empty public contact settings display "Not provided"; no email, phone, or address is invented. Survey submissions remain assigned to the correct business internally.
2. Save the notification email in Business Profile or Settings. These stay in sync; Dashboard's Email Alerts shortcut opens Settings. Saved profiles open in view mode with Edit Information; uploading a logo also updates the header avatar. Send Test Email checks mail configuration. Accept Responses controls whether responses can start or submit.
3. Share the unique Customer Survey Link from Settings. Each draft is pinned to that business, not the latest business in the database. The ordinary Home URL uses `CFS_BUSINESS_ID` or the explicit default business.
4. Manage Questions selects each question's section, display order, wording, hint, required state, and multiple-choice options. Core question type/section/required rules remain fixed to protect validation and rating calculations. Additional questions can be added, edited, archived, and restored.
5. Each draft freezes its question definitions. Submitted answers preserve original wording, type, and choices even after a question changes or is archived. Question changes apply to new drafts.
6. Feedback and Dashboard filters support dates, status, search, and daily/weekly/monthly trends. Counts/charts follow the selected scope. Export Filtered Results uses that scope; Export All Records is explicitly separate. Five-star displays convert the underlying 1-10 average; Resolution Rate means resolved responses divided by total responses.
7. Account settings support Admin name, login email, and password changes with current-password verification. Forgot Password issues expiring, single-use Laravel reset links without revealing whether an email is registered.

Live updates replace the existing dashboard content rather than appending duplicate layouts. Refresh keeps one header, workspace, and footer. Survey cards retain the existing theme and shared section styling.

## Admin Email Notifications

Set the recipient in **Settings > Notification Recipient Email** or Business Profile.
Business Profile email and Notification Recipient Email stay in sync. Profile email is preferred for existing records; changing it from Profile, Settings, or Dashboard updates the recipient everywhere. Login Email remains separate.
The business notification email is used first; the owner's account email is the fallback.
Notifications do not require the Admin to be logged in.

For actual inbox delivery, configure these `.env` settings with the mail provider's values:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-verified-sender@example.com
MAIL_FROM_NAME=CFS
APP_URL=http://127.0.0.1:8000
```

After changing configuration, run `php artisan config:clear`.
The current local environment uses `MAIL_MAILER=log`, which writes messages to `storage/logs/laravel.log` instead of delivering them.
Keep credentials in `.env`, outside version control. Use a reachable `APP_URL` when deploying so email links work outside this computer.

Email runs after the database transaction commits. SMTP failures are logged and do not undo saved feedback or the Admin notification.
An SMTP acceptance timestamp is recorded when the transport succeeds; inbox delivery still depends on the mail provider.
Feedback Details shows Sent, Logged, Failed, or Disabled status and offers Retry Email when it has not been sent. Notification preferences apply to automatic alerts; Send Test Email remains an explicit configuration check. Password reset links also require a working mail provider; local log output is not inbox delivery.

## Password Recovery and Gmail

Forgot Password uses the Admin Login Email in `users.email`, not the Business Profile notification address. The reset token is hashed in `password_resets`, expires after 60 minutes, is rate-limited, and can only be used once. A successful reset updates the password and consumes the token in one transaction. Failed delivery rolls back new token creation so a retry is not blocked by that failed request. Errors retain only the email; passwords, confirmation values, current passwords, and reset tokens are never flashed as old input.

Gmail transport defaults have been prepared (`smtp.gmail.com`, port `587`, TLS). Keep `MAIL_MAILER=log` until valid sender credentials are entered privately in `.env`. Log mode does not send inbox mail, and the Forgot Password result explicitly says so. When ready, use:

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=your-sender@gmail.com
MAIL_PASSWORD="your-google-app-password"
MAIL_FROM_ADDRESS=your-sender@gmail.com
MAIL_FROM_NAME=CFS
```

Google App Passwords require 2-Step Verification and may be restricted by Workspace policies. Use an App Password rather than the ordinary account password; do not put it in chat or version control. See [Google App Password guidance](https://support.google.com/mail/answer/185833?hl=en) and [Google SMTP settings](https://support.google.com/a/answer/176600?hl=en). Run `php artisan config:clear` after configuring credentials, then use Send Test Email in Settings. For deployment, `APP_URL` must be reachable from the device opening the reset link; `127.0.0.1` only reaches the same computer.

Password recovery opens a centered, keyboard-accessible modal over the original visible Admin Login card. The three states have their own URLs: `/forgot-password`, `/check-your-email`, and `/reset-password/{token}?email=...`. Login and recovery share a compact 332 x 352 card, 190 x 32 fields and equal 32px-high actions, with viewport constraints and internal scrolling on short screens. Shared background/branding/login markup is in `resources/views/auth/partials`; state content is in `resources/views/auth/recovery`. Common sizes are in `public/css/auth-panels.css`; recovery styling and behavior are in `public/css/password-recovery.css` and `public/js/password-recovery.js`.

The confirmation page requires a previous request in the same session. Refresh never sends another email. Both resend controls use the session-bound login email, a server-enforced 60-second cooldown for known and unknown accounts, and request throttling. Transport failures preserve retry access. Passwords and reset tokens are never flashed as old form input or stored in survey drafts.

Run `node tests/browser/password-recovery.cjs` for isolated browser verification of all three routed popups, refresh/Escape, a real cooldown/resend, generated emails, keyboard submission, validation, password reset, old/new login, token reuse rejection, and desktop/mobile/short-landscape layouts. It uses temporary SQLite data and local test mail output, never the real admin password or an actual Gmail inbox. The test deliberately waits for the one-minute resend cooldown.

## File Organization

The project uses Laravel's standard folder layout so routes, controllers, templates, and browser assets remain easy to find in VS Code:

- `app/Http/Controllers/`: request handlers grouped by feature name (`Auth`, `Dashboard`, `CustomerFeedback`, `Settings`, and others).
- `app/Http/Middleware/`: request/authentication middleware.
- `app/Models/`: database models.
- `app/Services/`: shared business logic, analytics, filters, and survey definitions.
- `app/Mail/` and `app/Notifications/`: outbound email and notification classes.
- `routes/web.php`, `routes/api.php`, `routes/channels.php`, `routes/console.php`: web, API, broadcast, and CLI routes.
- `resources/views/`: Blade screens; subfolders follow the app section (`auth`, `dashboard`, `feedback`, `questions`, `settings`, etc.). Reusable Blade components live in `resources/views/components/`.
- `public/css/`, `public/js/`, `public/icons/`: browser styles, scripts, and local icons, kept in their asset-type folders.
- `database/migrations/`, `database/seeders/`, `database/factories/`: schema changes and local demo/test data.
- `tests/Feature/`, `tests/Unit/`, `tests/browser/`, `tests/fixtures/`: server behavior, focused logic, browser checks, and private test-only assets.
- `storage/`: runtime logs, cached views/sessions, uploads, and private backups. Do not put source files here.

- `app/Http/Controllers/CustomerFeedbackController.php`: customer flow, validation, transactional submission.
- `app/Services/FeedbackNotifications.php`: dashboard notification and email delivery.
- `app/Services/SurveyDefinition.php`: core definitions, custom-question validation, and frozen question snapshots.
- `app/Services/FeedbackFilters.php`: shared record filters and chart periods.
- `app/Mail/AdminFeedbackSubmitted.php` and `resources/views/emails/`: Admin email content.
- `app/Http/Controllers/FeedbackReviewController.php`: authenticated review and status updates.
- `app/Http/Controllers/SettingsController.php` and `PasswordResetController.php`: notification settings, account changes, and password recovery.
- `resources/views/feedback/`: separate survey step templates and Admin feedback views.
- `resources/views/business/`, `questions/`, `settings/`: existing Admin section templates.
- `public/js/survey-draft.js` and `survey-form.js`: browser draft retention and survey controls.
- `public/js/admin-live.js`: nonduplicating live Admin panel updates.
- `public/js/admin-notifications.js`: live notification list, unread badge, individual record navigation, and mark-all-as-read.
- `app/Services/FeedbackSnapshot.php`: initial/live revisions for consistent chart, count, status, and notification refreshes.
- `public/css/workflow.css`: scoped workflow additions without a theme redesign.

The workflow migration adds business survey links, question ownership/archive fields, immutable answer snapshots, consent/email/resolution fields, and the `feedback_actions` audit table. Existing feedback is retained. Legacy unused form controllers/templates have been removed; each active survey step keeps its own view file with shared rendering partials.

## Verification

Run `php artisan test`. Tests force an in-memory SQLite database and never reset the configured development database.
Run `php artisan view:cache` to verify Blade compilation.

The browser regression test is `tests/browser/survey-flow.cjs`. It requires Playwright and installed Chrome.
Run `node tests/browser/survey-flow.cjs`; set `CFS_PHP` to the PHP executable if it is not available on Node's PATH.
It creates a temporary SQLite database and server, checks Admin question creation and settings, full survey/review flow, responsive card layouts, confetti motion, live updates, action history, archiving, and CSV export. Desktop/mobile screenshots are written to `storage/app/test-results`.
The focused `node tests/browser/admin-live.cjs` test uses its own temporary SQLite database to check Profile view/edit, logo/avatar rendering, synchronized notification recipients, live submissions/status charts, per-record notifications, and mobile layouts.

## Contact Messages and Date Filters

Contact Us uses a compact 480 x 360 desktop card, with a scrollable form and fixed actions. Optional name, required email, and message are stored separately in `contact_messages`; anonymous survey submissions remain available without follow-up. Contact mail uses `CFS_SUPPORT_EMAIL` when configured, otherwise the selected business's notification recipient. Duplicate submissions are protected by a session-bound token, failed saves retain entered text, and mail failure does not remove the saved message. Inbox delivery requires SMTP, just like feedback notifications.

Dashboard and Feedback share All dates, Today, Last 7 days, Last 30 days, This month, and Custom range filters. Presets are resolved on the server as well as in the UI; charts, totals, and filtered CSV exports use the same dates. Lucide SVG assets are stored locally in `public/icons/lucide` with their license.

## Additional Steps, Settings, and Keyboard Controls

The original five survey steps remain the default. Manage Questions > Add Step creates a draft. Add questions to the new section and Publish it to append Step 6, 7, and later steps for new responses. Empty or archived steps are excluded. Returning a step to draft, renaming it, or archiving it never rewrites existing customer drafts or saved answers. Archiving the final active question automatically unpublishes its additional step.

Settings is split into Account & Security, Notifications, Survey Access, and Share Survey. Notification and survey-access forms save independently. Current-password verification protects account changes. Dashboard and Contact Message alert switches control only in-app notification visibility; Contact Us messages are still saved to the owner-scoped Messages inbox and emailed to the configured recipient. Feedback email notifications have their own switch. Turning off notifications never deletes feedback or contact messages. Existing notifications remain available; viewing a contact notification marks that notification read.

Accept Responses and the closed-survey message are persisted in the database. Preview shows unsaved access settings without changing them. The survey link and PNG QR code use the actual business survey route. QR generation is local, using the MIT-licensed `qrcode-generator` library in `public/js/vendor/qrcode.js`; no customer information is sent to an external QR service. The URL must be reachable from the customer's device in deployment.

Focused buttons and links retain native Enter activation. Enter from a single-line form input submits its form through native validation; Enter on a radio/checkbox selects it. Textarea Enter inserts a newline; Ctrl+Enter (or Command+Enter) submits. These controls do not bypass required ratings, conditional comments, or duplicate-submission protection.

CSV exports are authenticated, business-scoped, and use the active filters unless All Records is selected. They include the customer label (Anonymous or Follow-up requested), submission details, status, admin/resolution notes, and individual historical question answers, including additional steps. The dedicated follow-up email is not exported. Text cells are protected against spreadsheet formula injection.

Run the browser test with `CFS_EXTRA_STEPS=1` to exercise the additional-step flow. The default run verifies the original five-step flow. Both check keyboard submission, Settings, QR decoding/download, survey access, review, submission, and CSV output. QR verification requires `jsqr` alongside Playwright.

## Responsive Layout

Settings uses four unframed groups within the shared admin workspace, with field icons and consistent 104 x 28 action buttons. Group gaps are 16px vertically/24px horizontally and field gaps are 8px vertically. Question views use Active/Archived tabs, and Add Step/Add Question share one aligned toolbar. Admin pages retain a viewport-based minimum height even when empty; longer records can grow naturally. Dashboard metric-detail grids constrain table minimum widths so the table region scrolls without widening the cards. Unread notification shortcuts include both feedback and contact-message notifications.

At tablet/phone widths the header navigation and admin menu collapse independently. They support Enter, Escape, and outside-click dismissal. Notifications have a compact, scrollable viewport-constrained popup with a close button. Tables scroll inside their own regions rather than stretching the page. The shared rules are in `public/css/responsive.css` and `public/js/responsive-navigation.js`.

The compact sidebar uses 38px items with 4px gaps. `public/js/chart-motion.js` animates rating bars, trend lines, status rings, and satisfaction gauges in a short 480ms pass on page entry/reload and after live panel replacements. Metric values render once at their final server value, avoiding a zero-to-final count-up on refresh. Each chart node initializes once, unchanged polling does not replay motion, and reduced-motion preferences are respected. Run `tests/browser/admin-live.cjs` with `CFS_CHART_MOTION_ONLY=1` to verify chart motion, refresh, single-ring rendering, and reduced-motion behavior.

`public/js/page-lifecycle.js` cancels old live/notification requests on full-page navigation and resumes them after Back/Forward restoration. Downloads, same-page anchors, new tabs and AJAX forms do not suspend updates. Run `node tests/browser/page-lifecycle.cjs` for its focused regression tests. Programmatic browser-test navigation explicitly suspends old-document polling before replacement; the test suite does not suppress page errors. This also avoids a [reported Playwright WebKit document-replacement diagnostic](https://github.com/microsoft/playwright/issues/43120).

Run `tests/browser/admin-live.cjs` with `CFS_RESPONSIVE_ONLY=1` for admin, public, contact, legal, login, and password recovery layout checks at 320, 390, 768, 813, 1024, and 1366 pixels. The full survey test checks every survey step at those widths too. Screenshots are saved privately under `storage/app/test-results`. These automated viewport checks supplement, rather than replace, testing on physical devices before deployment.

The responsive audit also covers all four Dashboard metric-detail routes, profile editing, and 667 x 320 landscape screens. Browser tests default to installed Chrome. Set `CFS_BROWSER_ENGINE=webkit` for an installed Playwright WebKit browser, and `CFS_TOUCH=1` for iPhone-style (WebKit) or Pixel-style (Chrome) device emulation. Set `PLAYWRIGHT_BROWSERS_PATH` to the test-engine directory when using a custom installation. Device emulation is not certification of every physical iOS/Android device. Before hosting, configure a reachable HTTPS `APP_URL`, production database/backups and private SMTP credentials; loopback survey links and local log mail cannot serve remote customers or an actual inbox.

## Dashboard Charts and Notification Inbox

The Dashboard overview uses a 1-10 rating distribution, rating trend and response-volume chart, followed by Recent Feedback and Notifications. Charts use the filtered database records. Daily/weekly/monthly graphs group submission periods containing records, including historical dates; an unanswered rating is not plotted as zero. Distribution counts only responses with numeric ratings. Existing weighted rating averages and metric-detail routes remain available.

Pending filter edits pause panel replacement until Apply/Clear or restoration of the applied values, so live polling cannot erase a date/status selection. Presets are resolved before validating stale manual dates. Notifications retain their existing popup constraints, with local Lucide icons, compact record details and right-aligned unread indicators. View All opens the authenticated, owner-scoped, paginated `/dashboard/inbox`, including read notifications and contact messages; `/dashboard/notifications` remains the JSON API.

Run `tests/browser/admin-live.cjs` with `CFS_DASHBOARD_ONLY=1` for historical chart data, visible animation, all presets, custom/status/period filters, matching CSV, pending-edit retention, notification read/navigation actions, icon loading and seven viewport checks. Tests use temporary SQLite data. MySQL connection outages render a standalone 503 retry screen or safe JSON without exposing SQL details; survey-specific retry handling remains unchanged.

## Feedback Details

Feedback Details is an unframed section inside the shared admin workspace. Compact metadata, follow-up details, visit/order details, rating answers and final/custom answers are shown alongside Admin Action Required. The Save Admin Action button is right-aligned; Action History is directly below that form in the same column. Core fields follow survey order; saved question labels/answers are preserved, and formatting does not change stored data. Status/notes controls are 30px/80px high, actions are 120 x 30, and the layout stacks on tablet/phone widths. Add Survey Step matches the compact Add Question width: 370px, constrained by the same 48px viewport margin.

Clicking the follow-up email opens the admin's email application; it does not send a message or resolve the record automatically. Record contact/internal work in Action Taken / Owner Notes, keep the concern open until addressed, and save Resolved only with resolution notes. Reported concerns cannot be downgraded to Submitted; Needs Action and the existing In Progress status remain available until resolution. Anonymous concerns can be resolved through documented internal action without an email. All action entries preserve the note, prior/new status, admin and timestamp; list/dashboard counts use saved statuses.

Run `tests/browser/survey-flow.cjs` with `CFS_FOLLOW_UP=1` for the full opt-in flow and admin-only email visibility. Without the flag it submits anonymously after testing opt-in validation, refresh, Back/Next and opt-out cleanup. Both cover compact Review Answers across the shared seven viewports.

Only the owning admin can view or update a record. Resolved requires an action note, failed validation retains the selected status/text, saves are transactional, and identical retries do not create duplicate history or change the resolution timestamp. Reopening clears the active resolution fields without deleting history. Email delivery labels remain truthful in local log/test mode. Run `tests/browser/admin-live.cjs` with `CFS_FEEDBACK_DETAILS_ONLY=1` for focused layout, modal-width, keyboard save, validation, history, retry and metrics checks using temporary SQLite data.

## Private Backups

Run `php artisan cfs:backup --source --label=before-change` before maintenance. It creates a database dump, a source/upload snapshot, and a completion manifest with a database SHA-256 checksum under `storage/app/backups`. MySQL uses `mysqldump --single-transaction`; SQLite uses `VACUUM INTO`. Failed backups remain marked incomplete and never reset existing data.

Backups include the private `.env` when `--source` is used. Keep this directory outside public hosting and version control. Restore to a separate database first when verifying recovery. This command is not a scheduled backup service; recurring backups require deployment scheduling and retention planning.

## Production Deployment

The locked production dependencies require **PHP 8.2 or newer**. Enable PHP extensions `openssl`, `pdo` with the MySQL driver, `mbstring`, `fileinfo`, and `gd` (GD is used for JPG exports). The project currently runs on Laravel 8; this release is legacy, so plan a separately tested framework upgrade before relying on long-term security support.

Use `.env.production.example` as a checklist, then create the real `.env` privately on the server. Set a public HTTPS `APP_URL`, `APP_ENV=production`, `APP_DEBUG=false`, a strong `APP_KEY`, production database credentials, and SMTP sender/credentials. Do not commit `.env` or copy real credentials into the example file. Generate `APP_KEY` only for a brand-new install; preserve the existing key on updates because changing it can invalidate encrypted application data. Recovery and CFS notification recipient addresses are independently controlled by the Admin account/settings; the SMTP sender address is only the sending identity.

For a new hosting environment, provision the database and deploy the source, then run:

```text
composer install --no-dev --optimize-autoloader
php artisan key:generate --force        # only for a brand-new installation
php artisan migrate --force
php artisan cfs:admin:create            # first Admin only; prompts privately for email/password
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan cfs:deploy-check
```

Skip `key:generate` for an existing installation. `cfs:deploy-check` is read-only: it checks production mode, HTTPS URL, key shape, required PHP extensions, writable runtime folders, database connection/tables/migrations, queue table, and real mail transport configuration. It never prints credentials or changes database records. Resolve every FAIL before launch. A PASS cannot prove that the hosting provider accepts SMTP or that messages reach inboxes; use Settings > Send Test Email and test a password-recovery code on the live host.

The app queues feedback submission alerts, admin action alerts, customer follow-up updates, Contact Us alerts, and admin replies. Keep a supervised database queue worker running continuously with `php artisan queue:work database --tries=3 --timeout=15`, and restart it after each release with `php artisan queue:restart`. Configure the process manager (for example Supervisor or the host's queue service) to restart it if it exits. Password recovery and verification OTP emails remain synchronous so the code can be delivered during the same request. All email flows still require working SMTP; queued mail is accepted by the app first and delivered by the worker. Schedule `php artisan cfs:backup --source --label=scheduled` through the host's scheduler, set retention, and regularly test restoring a backup to a separate database. The backup includes `.env`; keep it in private storage. Point the web server document root to `public/`, enable HTTPS, deny direct access to `.env` and `storage/app`, and ensure `storage/` plus `bootstrap/cache/` are writable by the PHP process. No recurring schedule is currently registered in `app/Console/Kernel.php`.

The current checkout is not deployed by these checks. Hosting domain, database, mail credentials, queue supervision, backup retention, and a successful live inbox test must be configured on the deployment target before it can be called production-ready.

Original Laravel Customer Feedback Survey code is licensed under the MIT license.
