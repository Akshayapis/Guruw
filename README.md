# Cashpe 💚 — UPI Payment Gateway

A PHP payment gateway adaptation with:

- **Brand new premium green UI** — marketing site, partner dashboard, admin console and checkout page.
- **Legacy payment flows** — adapted from the original PHP gateway. A custom database compatibility layer is used; test payment flows independently before production.
- **Firebase Firestore** — the MySQL/SQL database is replaced by a Firestore REST driver. No Composer, no SDK, no MySQL server.
- **Docker + Render ready** — one `Dockerfile`, `render.yaml`, Apache rewrites and a health endpoint.

> Business name changed from the old gateway name to **Cashpe** everywhere user-visible.

---

## 1. Run locally (zero configuration)

```bash
cd cashpe
docker compose up --build
# open http://localhost:8080
```

Without Firebase credentials the app uses the bundled JSON seed store at
`storage/db.json` (created from `storage/db.default.json`). Everything works
locally for development. Render local storage is ephemeral and unsuitable for production.

### Demo logins (seeded)

| Role | Username | Password |
|------|----------|----------|
| Admin console (`/Administration`) | `ADMIN` | `admin123` |
| Demo merchant | `DEMO` | `demo123` |

Sign-up and admin add-merchant use only name, business name, mobile, email and
password — no PAN / Aadhaar is required for registration. Self-service password recovery is not configured; an administrator must reset passwords.

---

## 2. Connect Firebase (Firestore)

For Firestore and Realtime Database rules and Hindi setup, see `FIREBASE_RULES_SETUP.txt`. This app uses Firestore, not Realtime Database.

1. Create a Firebase project → **Firestore Database** → create database.
2. Create a service account: Firebase console → Project settings → Service
   accounts → **Generate new private key**. Download the JSON file.
   **Never commit this file.**
3. Set these environment variables (the JSON values, not the file path, are
   easiest on Render):

   | Variable | Value |
   |----------|-------|
   | `FIREBASE_PROJECT_ID` | project id from the JSON |
   | `FIREBASE_CLIENT_EMAIL` | `client_email` from the JSON |
   | `FIREBASE_PRIVATE_KEY` | `private_key` from the JSON (newlines are fine) |
   | `APP_BASE_URL` | your public host, e.g. `cashpe.onrender.com` |

   Alternatively set `FIREBASE_SERVICE_ACCOUNT` to the whole JSON (plain or
   base64) or `FIREBASE_SERVICE_ACCOUNT_PATH` to a mounted file path.
4. Full step-by-step guide: `docs/FIREBASE_SETUP.md`. Security rules are in
   `firestore.rules` (deploy with `firebase deploy --only firestore:rules`).
5. Seed the data into Firestore:

   ```bash
   FIREBASE_PROJECT_ID=... FIREBASE_CLIENT_EMAIL=... FIREBASE_PRIVATE_KEY=... \
     php tools/seed_firebase.php
   ```

   Do not use `--reset` in production: it deletes existing data. The default command skips existing document IDs. The seed does not migrate original MySQL data. Change public default passwords immediately.

Documents are stored one collection per original table: `tb_partner`,
`tb_transactions`, `tb_virtualtxn`, `tb_upis`, `tb_settings`, `tb_slab`,
`tb_gateway`. All values are stored as strings, exactly as the original code
used them, so no query behaviour changes.

---

## 3. Deploy on Render

The repository-root `render.yaml` deploys the `cashpe/` directory as a Docker
web service. If you upload only the contents of `cashpe/` as a repository root,
use the `render.yaml` inside that directory instead:

1. Push this repository to GitHub.
2. Render Dashboard → **New** → **Blueprint** → select the repository.
3. Fill in the secret environment variables when prompted
   (`APP_BASE_URL`, `FIREBASE_PROJECT_ID`, `FIREBASE_CLIENT_EMAIL`,
   `FIREBASE_PRIVATE_KEY`).
4. Render builds the Docker image and starts Apache on `$PORT`.
   Set `APP_BASE_URL` to the hostname Render gives you (without `https://`).

Manual setup: create a **Web Service → Docker**, set **Root Directory** to
`cashpe`, **Dockerfile Path** to `./Dockerfile`, then add the same env vars.

`GET /health.php` returns `{"status":"ok","database":"firestore"|"local",...}`.

---

## 4. Scope and important limitations

The payment and UPI implementation was inherited from the supplied project;
its integrations require real merchant credentials and end-to-end testing.
The database adapter, authentication, deployment and UI have been changed.
The custom SQL-to-Firestore adapter is not a full relational database and has
not been validated for concurrent production payments. Back up your data and
obtain a security and payment-flow review before live use. The bundled sample
seed is not a migration of your existing MySQL data.

---

## 5. External services

Payment status verification talks to the merchant's configured Paytm/Hypto
and SMS endpoints. Put real merchant credentials in the
admin panel (**Settings / UPI Settings**) for live payments. The demo merchant
is pre-configured for the built-in trial flow.

## Admin recovery and mobile navigation

If the existing administrator cannot sign in, check `/health.php` and follow
section 6 of `FIREBASE_RULES_SETUP.txt` for an operator-only password reset.
Existing Firestore users are never automatically overwritten. The partner
dashboard remains visible when a plan has expired; other legacy feature gates
still require activating a plan or trial. The mobile navigation is opened using
the Menu button in the top bar.

## Local regression checks

```sh
python3 tests/check_assets.py
node tests/sidebar.test.js
php tests/firestore_id_test.php
php tests/firestore_cache_test.php
# Isolated, in-memory REST mock: never point these tests at a live database.
node tests/mock_firestore_server.mjs &
php tests/firestore_http_test.php
```

The bundled original archive has no `assets/img` image files and no Mitra
Gateway APK. Local labelled display badges are supplied; the missing APK must
come from the original provider. Login and signup behavior, Firestore mock,
mobile navigation and a signed sample checkout have been tested locally, not
against a live payment provider or the user's Render/Firebase deployment.
