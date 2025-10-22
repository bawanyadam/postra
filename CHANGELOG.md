Postra Changelog
================

v1.2.0 — 2025-10-22
--------------------

Highlights
- Redesigned admin navigation with a permanent sidebar, matching mobile hamburger menu, and refreshed Postra branding.
- Added Cloudflare Turnstile verification plus additional spam-guard heuristics to cut down automated submissions.
- Revamped public-facing assets: new purple landing page, white logo, and favicon/touch/OG metadata for better sharing.

Details
- Introduced dedicated New Project/New Form routes with updated create actions in the sidebar.
- Synced mobile navigation styling with the desktop sidebar, including project accordions, hover states, and brand logo.
- Tweaked embed snippets and other UI elements to wrap cleanly on smaller screens.
- Updated logos across the app and refreshed `/` styling with responsive logo sizing.
- Wired in favicons, Apple touch icons, and social preview images sourced from `postra.to`.
- Added bulk submission delete controls and Turnstile server-side verification (skips when keys are not configured).
- Hardened spam detection for gibberish names, URLs, and crypto keywords.

Upgrade Notes
- Optional: set `TURNSTILE_SITE_KEY` and `TURNSTILE_SECRET_KEY` in `.env` to enforce Cloudflare Turnstile checks.
- Run `composer dump-autoload -o` after pulling to ensure new services are autoloaded.

v1.0.0 — 2025-10-16
--------------------

Highlights
- Admin UI with Bootstrap (Projects, Forms, Submissions, Settings)
- Authentication (login/logout) with CSRF protection
- Public capture endpoint: `POST /form/{public_id}` stores in MySQL
- Email delivery via SendGrid with encrypted API key storage (Sodium)
- Clean HTML/text email template; subject: `Submission: {Form Name}`
- CSV export: global and per-form submissions
- Pagination for submissions
- Dynamic page titles and breadcrumbs across the app
- Minimal public landing page at `/` with centered logo

Details
- Projects
  - List, create, and edit (name, description)
  - Per-project forms with quick create
- Forms
  - Show/update settings (recipient, redirect URL, allowed domain, status)
  - Send Test Email action
  - Per-form submissions view with pagination + CSV export
- Submissions
  - Global list with project/form context
  - Submission detail view with full payload and metadata
  - CSV export endpoints
- Email
  - Subject changed to `Submission: {Form Name}`
  - Template with readable tables; Reply-To auto-detection (`email`, `reply_to`, `_replyto`, etc.)
  - From address configurable via `.env` (`POSTRA_FROM_EMAIL/NAME`)
- Security
  - Sessions hardened; CSRF tokens on all forms
  - Secrets encrypted using `POSTRA_ENCRYPTION_KEY_BASE64` (libsodium secretbox)
- Routing/UI polish
  - Dynamic titles and breadcrumbs
  - Root landing page (white background, centered logo)

Developer Notes
- New ULID generator avoids zero-heavy suffixes for nicer IDs
- README revamped with production setup and troubleshooting

Upgrade Notes
- No schema changes since initial migration; run `git pull` and reload Apache
- Ensure `.env` has `POSTRA_ENCRYPTION_KEY_BASE64` and `SESSION_SECRET` set
- After pulling:
  - `composer dump-autoload -o`
  - `sudo systemctl reload apache2`
