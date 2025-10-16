Postra
======

A tiny, zero-framework PHP 8 app for receiving website form submissions, storing them in MySQL, and forwarding them by email. It ships with a clean Bootstrap UI for managing Projects, Forms, and Submissions.

Key features
------------

- Admin UI (login/logout, CSRF-protected)
- Projects and Forms management
- Public capture endpoint: `POST /form/{public_id}`
- Email delivery via SendGrid (encrypted at rest)
- CSV export (global submissions and per form)
- Pagination for submissions lists
- Simple, readable email template (HTML + text) with Reply-To detection

Stack
-----

- PHP 8.x (PDO, cURL, Sodium)
- MySQL 8.x
- Apache 2.4 (or PHP dev server for local)
- Bootstrap 5 via CDN

Directory layout
----------------

- `public/` — front controller (`index.php`) and web root
- `app/` — controllers, services, infrastructure, views
- `migrations/` — SQL schema
- `scripts/` — migration and admin seeding

Quick start (local)
-------------------

- Copy `.env.example` to `.env` and set DB creds.
- Ensure a MySQL 8 database is reachable per `DB_DSN`.
- Install dependencies: `composer install`
- Run migrations: `php scripts/migrate.php`
- Create an admin user: `php scripts/seed_admin.php admin yourpassword`
- Start dev server: `php -S 0.0.0.0:8000 -t public`
- Visit `http://localhost:8000/app`

Production Setup (Ubuntu + Apache + PHP 8 + MySQL)
--------------------------------------------------

Prereqs: Ubuntu, Apache 2.4, PHP 8.x, MySQL 8. You need sudo access.

1) Packages

```
sudo apt update
sudo apt install -y git unzip curl \
  apache2 \
  php php-cli php-mbstring php-xml php-curl php-mysql php-zip php-intl
```

Sodium is usually compiled into Ubuntu PHP. Verify with: `php -i | grep -i sodium`.

2) Composer

```
cd /usr/local/bin
sudo curl -fsSL https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

3) Deploy code

```
sudo mkdir -p /var/www/postra
sudo chown $USER:$USER /var/www/postra
cd /var/www/postra
git clone https://github.com/bawanyadam/postra.git .
composer install --no-dev -o
cp .env.example .env
```

4) Configure environment

Edit `.env`:

- `APP_ENV=production`
- `APP_URL=https://your-domain`
- `DB_DSN=mysql:host=127.0.0.1;port=3306;dbname=postra;charset=utf8mb4`
- `DB_USER=postra` and `DB_PASS=...`
- `SESSION_SECRET=` set to a random string (32+ chars)
- Generate encryption key for secrets and set `POSTRA_ENCRYPTION_KEY_BASE64`:

```
php -r 'echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)), PHP_EOL;'
```

Optional email identity (defaults shown):

- `POSTRA_FROM_EMAIL=submission@postra.to`
- `POSTRA_FROM_NAME=Postra`

5) Database and admin

```
php scripts/migrate.php
php scripts/seed_admin.php admin StrongPasswordHere
```

6) Apache vhost

Create `/etc/apache2/sites-available/postra.conf`:

```
<VirtualHost *:80>
    ServerName your-domain
    DocumentRoot /var/www/postra/public

    <Directory /var/www/postra/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/postra_error.log
    CustomLog ${APACHE_LOG_DIR}/postra_access.log combined
</VirtualHost>
```

Enable site and rewrite, then reload:

```
sudo a2enmod rewrite
sudo a2ensite postra
sudo systemctl reload apache2
```

7) Routing (.htaccess)

If not using a global rewrite rule, add `public/.htaccess`:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

8) Permissions

```
sudo chown -R www-data:www-data /var/www/postra
```

First Run
---------

- Visit `https://your-domain/app` and log in with the admin you seeded.
- Go to Settings → Email, paste your SendGrid API key, and send a test.
- Create a Project and a Form.

Embed Snippet
-------------

Use the Action URL shown on the form page. Example:

```
<form action="https://your-domain/form/01HABCDEFULIDEXAMPLE000000" method="POST">
  <input type="text" name="name" required>
  <input type="email" name="email" required>
  <textarea name="message" required></textarea>
  <button type="submit">Send</button>
</form>
```

Exports
-------

- Global submissions → “Export CSV” (`/app/submissions/export.csv`)
- Per-form submissions → “Export CSV” (`/app/forms/{id}/submissions/export.csv`)

Environment Reference
---------------------

- `APP_ENV` — `production` or `local`
- `APP_URL` — base URL used in embed snippets
- `DB_DSN`, `DB_USER`, `DB_PASS`
- `SESSION_SECRET` — random string used for sessions
- `POSTRA_ENCRYPTION_KEY_BASE64` — base64 key for encrypting secrets
- `POSTRA_FROM_EMAIL`, `POSTRA_FROM_NAME` — email identity

Troubleshooting
---------------

- MySQL access denied (1045):
  - Confirm `DB_USER`/`DB_PASS`; ensure user exists as `'postra'@'localhost'` (or your host) and has privileges.

- Unknown database (1049):
  - Create DB first or run `php scripts/migrate.php` after pointing `DB_DSN` at an existing server.

- Decryption failed / email test fails:
  - Ensure `POSTRA_ENCRYPTION_KEY_BASE64` is set before saving the SendGrid key. Regenerate if needed and re-save the key.

- Not Found at your domain root:
  - Verify vhost points to `/var/www/postra/public` and rewrites to `index.php`.

- Apache error logs:
  - Check `/var/log/apache2/postra_error.log` (or the path from your vhost).

- Git “dubious ownership” warning:
  - `git config --global --add safe.directory /var/www/postra`

Updating
--------

```
cd /var/www/postra
git pull --ff-only
composer dump-autoload -o
sudo systemctl reload apache2
```

Security Notes
--------------

- Sessions are cookie-based and protected with CSRF tokens in forms.
- SendGrid API keys are stored encrypted using Sodium secretbox with your `POSTRA_ENCRYPTION_KEY_BASE64`.
- The public capture endpoint accepts only POST and strips any `_postra_*` reserved fields.

Roadmap
-------

- Allowed-domain enforcement for capture
- Resend email action from submission view
- Rate limiting and spam controls (honeypot, timing threshold)
