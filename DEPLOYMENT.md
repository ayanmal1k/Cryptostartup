# Deployment — xtrafreaky.com (VPS + Laravel Forge)

Goal: run this Laravel app on a real server, deploy it from GitHub, and point
`xtrafreaky.com` at it with HTTPS.

> A GoDaddy **domain** is only the address. You still need a **server** (VPS) to run PHP/MySQL.
> GitHub stores the code; Laravel Forge connects to GitHub and deploys it to the server.

---

## 0. Before you start — read this

- **Crypto is still in simulated/dev mode.** Deploying does NOT make NFTs/coins real; that
  needs the thirdweb setup (see `WHAT_WE_BUILT.md`). A live deploy = a working site with demo crypto.
- **Licensing:** this is built on JustFans (paid script). Ensure the client has a valid license
  for production. JustFans also has a built-in installer/license check — you may need to complete
  the `/install` step (or its license verification) on the live server.
- **Adult content?** If the site hosts adult content, you must pick a **provider that allows it**
  (verify the provider's Acceptable Use Policy — e.g. Hetzner/Vultr generally allow legal adult
  content; many do not) and an **adult-friendly payment processor** (CCBill is already supported;
  Stripe/PayPal prohibit it). GoDaddy shared hosting and many mainstream hosts ban it.

---

## 1. Create the server (Laravel Forge)

1. Sign up at **forge.laravel.com** (~$12/mo) and at a server provider
   (DigitalOcean / Hetzner / Vultr — ~$6–12/mo; pick one whose AUP fits your content).
2. In Forge → connect your **server provider** (API token) and your **GitHub** account.
3. **Create Server** → choose provider/size (2GB+ RAM), PHP **8.2**, MySQL. Forge installs
   Nginx, PHP, MySQL, Composer, Node, Redis, etc. automatically.

## 2. Create the site + connect GitHub

1. Forge → your server → **New Site**:
   - Root domain: `xtrafreaky.com`
   - **Web directory: `/public`**  ← important (Laravel serves from /public)
   - PHP 8.2
2. On the site → **Git Repository** → provider GitHub, repo `saqib387/jeremiahesters_new`,
   branch `master`. (This is the "link GitHub" part.)

## 3. Environment (.env)

Forge → site → **Environment**. Set production values:

```env
APP_NAME="Xtrafreaky"
APP_ENV=production
APP_DEBUG=false
APP_KEY=               # generate: see step 5, or `php artisan key:generate --show`
APP_URL=https://xtrafreaky.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=forge      # Forge creates one; use its name
DB_USERNAME=forge
DB_PASSWORD=           # from Forge's DB section

QUEUE_CONNECTION=sync  # fine to start; switch to a worker when going live on-chain
SESSION_DRIVER=file
CACHE_DRIVER=file
FILESYSTEM_DISK=public # consider S3 later so uploads survive redeploys

MAIL_MAILER=smtp       # real SMTP for verification emails
# ... MAIL_* ...

# Crypto stays simulated until these are filled (see WHAT_WE_BUILT.md):
WEB3_DRIVER=auto
WEB3_STORAGE_DRIVER=auto
WEB3_CHAIN_ID=80002
# THIRDWEB_CLIENT_ID=, WEB3_ENGINE_URL=, etc.
```

## 4. Deploy script

Forge → site → **Deploy Script**. Use this (note the caveats):

```bash
cd $FORGE_SITE_PATH
git pull origin $FORGE_SITE_BRANCH
$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader
npm ci
npm run prod
$FORGE_PHP artisan migrate --force
$FORGE_PHP artisan storage:link || true
$FORGE_PHP artisan view:cache
$FORGE_PHP artisan queue:restart
```

> **Do NOT add `php artisan route:cache`** — this app has closure-based routes in `routes/web.php`,
> and route caching fails on those. `config:cache` is also omitted on purpose (this legacy
> codebase may call `env()` at runtime); enable it only after verifying the site works with it.

## 5. First deploy + initialize

1. Click **Deploy Now** in Forge.
2. In Forge → site → **Commands** (or SSH), run once:
   ```bash
   php artisan key:generate --force      # if APP_KEY wasn't set
   php artisan migrate --seed --force    # first time only, creates tables + seed data
   php artisan storage:link
   ```
3. If JustFans shows an installer/license screen, complete `/install` as the script requires.

## 6. Point the domain (GoDaddy DNS)

In **GoDaddy → your domain → DNS → Manage DNS**:
- **A record**: Host `@` → Value = your server's **IP address** (from Forge), TTL default.
- **A record** (or CNAME): Host `www` → same IP (or CNAME to `xtrafreaky.com`).
- Remove GoDaddy's default parking/forwarding records if present.

DNS can take 15 min–a few hours to propagate.

## 7. HTTPS

Forge → site → **SSL → Let's Encrypt** → issue for `xtrafreaky.com` and `www.xtrafreaky.com`.
(Do this after DNS points at the server.)

## 8. Post-launch checklist

- [ ] Change the default admin password (`admin@admin.com` / `password`).
- [ ] `APP_DEBUG=false` confirmed.
- [ ] Email sending works (verification).
- [ ] Set up a **queue worker** in Forge if you switch `QUEUE_CONNECTION` off `sync`.
- [ ] Configure a payment processor appropriate to your content type.
- [ ] (When ready) add thirdweb credentials to make crypto real.
- [ ] Backups (Forge has scheduled DB backups).

---

After this, every `git push` to `master` → click **Deploy** (or enable auto-deploy) and the live
site updates.
