Postra
===========

Internal form processing.

Quick start
-----------

- Copy `.env.example` to `.env` and edit DB creds.
- Ensure a MySQL 8 database is reachable per `DB_DSN`.
- Run the migration script:

  `php scripts/migrate.php`

- Seed an admin user (Argon2id hash stored in DB):

  `php scripts/seed_admin.php admin yourpassword`

- Serve the app locally (PHP dev server):

  `php -S localhost:8000 -t public`

- Visit `http://localhost:8000/app` for the admin placeholder.

Test submit
-----------

Insert a test form row, then submit to it:

1. Create a project and form (example SQL):

   `INSERT INTO projects (public_id, name) VALUES ('01TESTPROJECTULID000000000000', 'Demo');`

   `INSERT INTO forms (public_id, project_id, name, recipient_email, redirect_url) VALUES ('01TESTFORMULID00000000000000', 1, 'Contact', 'you@example.com', '/app');`

2. Post a form to `http://localhost:8000/form/01TESTFORMULID00000000000000`.

Notes
-----

- This MVP uses a tiny internal router and PDO. We can swap in FastRoute and other libs once dependencies are installed.
- Email delivery via SendGrid and full admin UI come in next phases.
  - To configure SendGrid: insert an API key into `api_credentials` table.
    - For a global key: encrypt with `POSTRA_ENCRYPTION_KEY_BASE64` (Sodium secretbox) and insert a row with `provider='sendgrid', scope='global', scope_ref_id=NULL`.
    - You can also scope by project or per form.


Production setup (Ubuntu + Apache + PHP 8 + MySQL)
--------------------------------------------------

Prereqs: A clean server with Ubuntu, Apache 2.4, PHP 8.x, and MySQL 8 running. Ensure you have sudo access.

1) Install required packages

```
sudo apt update
sudo apt install -y git unzip curl \
  php php-cli php-mbstring php-xml php-curl php-mysql php-sqlite3 php-zip php-intl
```

- Optional but recommended: ensure sodium is enabled (usually built-in on Ubuntu’s PHP packages).

2) Install Composer

```
cd /usr/local/bin
sudo curl -fsSL https://getcomposer.org/installer -o composer-setup.php
sudo php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php
composer --version
```

3) Deploy Postra

```
sudo mkdir -p /var/www/postra
sudo chown $USER:$USER /var/www/postra
cd /var/www/postra
git clone <your_repo_url> .
composer install
cp .env.example .env
```

4) Configure environment

- Edit `.env` and set:
  - `APP_URL` to your site URL (https preferred)
  - `DB_DSN`, `DB_USER`, `DB_PASS` to point to your MySQL instance
  - Generate encryption key (Sodium secretbox) and add to `POSTRA_ENCRYPTION_KEY_BASE64`:

```
php -r 'echo base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)), PHP_EOL;'
```

5) Create database and run migrations

```
php scripts/migrate.php
```

6) Seed an admin user

```
php scripts/seed_admin.php admin StrongPasswordHere
```

7) Apache vhost

Create `/etc/apache2/sites-available/postra.conf`:

```
<VirtualHost *:80>
    ServerName your-domain.example
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

8) Add public/.htaccess for routing

Create `public/.htaccess` with:

```
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

9) Secure

- Set correct ownership (if serving under www-data):

```
sudo chown -R www-data:www-data /var/www/postra
```

- Ensure only necessary ports are open (80/443). Consider enabling HTTPS with Let’s Encrypt (certbot).

10) First run

- Visit `http://your-domain/app` and sign in with the seeded admin user.
- Go to Settings → Email to add your SendGrid API key and send a test.
- Create a Project and a Form; use the provided HTML snippet to test submissions.
# postra
