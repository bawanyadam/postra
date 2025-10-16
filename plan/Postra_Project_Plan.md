# Postra Project Plan

A lean internal Formspree-style service for handling HTML form submissions, storing them, and emailing results via SendGrid. Built with PHP 8, MySQL, Apache on Ubuntu. Light on dependencies, modern patterns, and a clear path to grow later.

---

## 1) Goals and scope

- Internal tool for a single admin user today, designed to grow to multi-user later.
- Core model: **Projects → Forms → Submissions**.
- Public capture endpoint at `https://postra.to/form/{public_id}` that accepts `POST` and redirects on success.
- Management UI at `https://postra.to/app/` for creating and managing projects, forms, credentials, and viewing submissions.
- Email delivery through **SendGrid** with a key that you set inside the app. Per-form or per-project overrides are possible.
- Minimal framework footprint, PSR-friendly components.

---

## 2) Tech stack and key dependencies

- **Language/Runtime**: PHP 8.x
- **Web server**: Apache 2.4 on Ubuntu
- **Database**: MySQL 8.x (utf8mb4)
- **Composer packages** (suggested):
  - Routing: `nikic/fast-route`
  - HTTP foundation: `symfony/http-foundation`
  - Templates: `league/plates` (or `twig/twig`)
  - Validation: `respect/validation`
  - Email: `sendgrid/sendgrid`
  - Logging: `monolog/monolog`
  - Env/config: `vlucas/phpdotenv`
  - IDs: `ramsey/uuid` with ULID support or `symfony/uid`
  - Optional DB layer: native PDO or `doctrine/dbal`

---

## 3) High-level architecture

- **Front controller**: `public/index.php` routes all requests.
- **Routing**: FastRoute maps to controllers under `/app` and `/form`.
- **Controllers**: Thin classes that accept Request, call Services, and return Responses.
- **Services**: Business logic (FormService, SubmissionService, Mailer).
- **Infrastructure**: PDO connection factory, migrations runner, SendGrid mail adapter, crypto utils for secrets.
- **Views**: Server-rendered management screens with a lightweight template engine.
- **Config**: `.env` provides runtime config. Credentials stored encrypted at rest in DB.

---

## 4) Directory layout

```
/app
  /Config
  /Domain          # Entities: Project, Form, Submission
  /Http
    /Controllers   # AppController, AuthController, ProjectController, FormController, CaptureController
    /Middleware    # Auth, CSRF, optional rate limit
    /Views         # Templates
  /Infrastructure  # DB, Mail, Security, Migrations
  /Services        # SubmissionService, FormService, CredentialService
  /Support         # Helpers
/migrations
/public
  index.php
  /assets
/.env
/composer.json
```

---

## 5) Routing and endpoints

**Management UI (auth required)**
- `GET /app/` dashboard
- `GET|POST /app/login` login
- `POST /app/logout` logout
- `GET /app/projects` list, `POST /app/projects` create
- `GET /app/projects/{id}` view project and child forms
- `GET|POST /app/forms/new?project={id}` create form
- `GET /app/forms/{id}` overview
- `GET /app/forms/{id}/submissions?cursor=...` paginated list
- `GET|POST /app/forms/{id}/settings` edit recipient email, redirect URL, allowed domain, status, credential selection
- `GET|POST /app/settings/email` manage global SendGrid key

**Public**
- `POST /form/{public_id}` capture endpoint; stores submission, sends email, redirects with 303

---

## 6) Data model overview (no SQL here)

- **users**: single admin user initially (username, password_hash, is_admin)
- **projects**: `public_id` (ULID), name, description
- **api_credentials**: provider=`sendgrid`, scope in {global, project, form}, encrypted secret
- **forms**: `public_id` (ULID), project_id, name, recipient_email, redirect_url, allowed_domain, status, optional api_credential_id
- **submissions**: form_id, created_at, client_ip, user_agent, payload_json, dedupe_hash
- **submission_fields** (optional): flattened key/value for search

Indexes and cascade rules are applied to support fast listing and safe deletes.

---

## 7) Security model

- **Management UI**: cookie session with HttpOnly, Secure, SameSite=Lax. CSRF tokens on all POST routes. Password hashing via Argon2id. Basic login rate limit per IP.
- **Public capture**: POST only; reject GET. Optional origin or referrer check against `allowed_domain`. Optionally require a request token `_postra_token` (HMAC of `{public_id}:{allowed_domain}`).
- **Size limits**: cap body size and per-field size. Sanitize values and escape on render.
- **Anti-spam**: honeypot field `_hp` must be empty, submission time threshold, and per-IP throttling.
- **Secrets**: SendGrid keys stored encrypted at rest and only decrypted at send time. No secret ever logged.
- **Headers**: CSP for `/app`, HSTS for the domain.

---

## 8) Submission pipeline

1. Route `POST /form/{public_id}` and look up the form.
2. Validate status is active; verify origin/referrer or `_postra_token` if configured.
3. Collect fields from `$_POST`, normalize to strings/arrays. Strip reserved names that start with `_postra_`.
4. Start a transaction:
   - Insert into `submissions` with payload JSON, IP, UA, dedupe hash.
   - Optionally expand `submission_fields` for search.
5. Resolve SendGrid credential: per-form, then per-project, then global.
6. Create a clean HTML email:
   - Title: “New submission — {Form Name}”
   - Meta: date/time, project, form, IP, UA
   - Key/value table, with arrays rendered as lists
   - Plain-text alternative
   - `Reply-To` if a valid `email` field is present
7. Send email via SendGrid; on failure, log and mark the submission for retry.
8. `303 See Other` to the form’s `redirect_url` (or a validated per-submit `_redirect` if allowed).

---

## 9) Management UI details

- **Dashboard**: counts and a small submissions sparkline for the last 7–30 days.
- **Projects**: create, list, and view forms. Show last submission time and 24h count.
- **Forms**:
  - Settings: name, project, recipient email(s), redirect URL, allowed domain, status, credential selection.
  - Implementation snippet showing `action` URL, method, token if enabled, and a pasteable example form.
  - Submissions: paginated list with created time, common fields, and detail view.
  - Export: CSV and JSON for a date range. Resend email button on a submission.
- **Settings → Email**: manage global SendGrid key, masked display, “Send test email” button.

---

## 10) Email delivery with SendGrid

- Adapter class that accepts a submission DTO, form metadata, and the chosen SendGrid key.
- Adds useful headers: `X-Postra-Form-Id`, `X-Postra-Submission-Id`.
- Retries with backoff on transient failures. Falls back to marking `email_failed=1` for the submission in DB.

---

## 11) Configuration and secrets

- `.env` keys:
  - `APP_ENV`, `APP_URL`
  - `DB_DSN`, `DB_USER`, `DB_PASS`
  - `SESSION_SECRET`
  - `POSTRA_ENCRYPTION_KEY_BASE64` for encrypting credentials
- Encryption uses libsodium. Keys rotated by decrypting and re-encrypting stored secrets. A small UI flow can guide rotation.

---

## 12) Validation, spam control, and dedupe

- Management: validate emails and URLs, confirm destructive actions, and mask secrets.
- Public submissions: honeypot, submit-time threshold, per-IP throttling. Compute `dedupe_hash = sha256(form_id + normalized_payload)` to drop exact duplicates within a short window.

---

## 13) Logging and observability

- **Monolog** to daily rotated files: auth events, capture success/fail, email success/fail.
- Request ID on each response for easy tracing.
- Health page at `GET /app/health` (auth required) that checks DB connection and pending migrations count.

---

## 14) Backups and maintenance

- Nightly `mysqldump` of core tables with encryption at rest. Store off the server.
- Rotate app logs and compress older archives.
- Optional: SendGrid event webhook (phase 2) to record delivery/bounce.

---

## 15) Migrations and seed

- Simple PHP migration runner or a tool like Phinx. Migrations define tables and indexes.
- Seed:
  - One admin user with username and password hash from env or an initial setup screen.
  - Example project and form for a smoke test.

---

## 16) Apache and deployment

- VHost points `DocumentRoot` to `/var/www/postra/public`.
- Rewrite all non-file requests to `public/index.php`.

**`.htaccess`**

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

- Force HTTPS at the VHost and enable HSTS.
- Systemd service or simple process supervision for queue workers if you add async jobs later.

---

## 17) Implementation milestones

**Phase 0: Bootstrap**  
- Composer init, autoloading, folder scaffolding, front controller, routing, templates.

**Phase 1: Core data and auth**  
- Migrations, DB connection, admin login/logout, CSRF, Projects and Forms CRUD.

**Phase 2: Capture and email**  
- Implement `POST /form/{public_id}`. Persist submission, send email, redirect. Submissions list and detail.

**Phase 3: Settings and polish**  
- Global SendGrid key UI with encryption and test send. Implementation snippet generator. CSV/JSON export. Resend email.

**Phase 4: Hardening**  
- Honeypot, throttling, dedupe, better error screens, soft delete, access logs, health page.

---

## 18) Testing checklist

- **Unit**: token generator, email renderer, credential resolver, validators.
- **Integration**: create form → submit → row in DB → email “sent” (mock in dev) → 303 redirect.
- **Edge cases**: disabled form, empty POST, large payload, duplicate within window, invalid token.
- **Security**: CSRF in `/app`, session cookies, strong password hash, secrets never logged.

---

## 19) Backlog and nice to have

- File attachments with type and size whitelist, sent via SendGrid.
- Per-field routing (route to different recipients based on a field value).
- Delivery status via SendGrid events, bounce handling.
- Multi-user roles (admin, editor, read-only), audit log.
- JSON API for submissions and admin actions.
- Tagging and full-text search across `submission_fields`.
- Theming for the management UI.

---

## 20) Snippets for documentation

**HTML form implementation snippet**

```html
<form action="https://postra.to/form/01JEXAMPLEULID1234567890AB" method="POST">
  <input type="hidden" name="_postra_token" value="HMAC_SIGNATURE">
  <input type="text"   name="name" required>
  <input type="email"  name="email" required>
  <textarea name="message" required></textarea>
  <button type="submit">Send</button>
</form>
```

**Successful redirect behavior**  
Server issues `303 See Other` to the configured `redirect_url` (you can also allow a validated `_redirect` override per submission).

**Email layout highlights**  
- Title: “New submission — {Form Name}”  
- Metadata block with timestamp, project, form, IP, UA  
- Key/Value table, arrays rendered as lists  
- Plain text alternative part

---

_End of document._
