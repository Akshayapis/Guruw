# Cashpe — Firebase (Firestore) full setup

Follow this once. The app works with the bundled local JSON store until the
environment variables below are set; after that every query goes to Firestore.

## 1. Create the Firebase project

1. Go to https://console.firebase.google.com → **Add project** → name it (e.g. `cashpe-prod`).
2. Build > **Firestore Database** → **Create database** → choose a region near your users
   (for India, `asia-south1` / Mumbai) → **Production mode**.
3. (Optional, for rules deployment) Enable the **Firebase CLI**:

   ```bash
   npm install -g firebase-tools
   firebase login
   cd cashpe
   firebase use --add        # select your project id
   firebase deploy --only firestore:rules
   ```

   The included `firestore.rules` blocks direct client access. Your PHP service
   account bypasses rules and is governed by IAM. Do not grant all authenticated
   clients unrestricted access to payment and merchant data.

## 2. Create a service account

1. Firebase console → ⚙️ **Project settings** → **Service accounts**.
2. Click **Generate new private key** → download the JSON. **Never commit it.**
3. Note these three values from the JSON:
   - `project_id`
   - `client_email`
   - `private_key` (includes `-----BEGIN PRIVATE KEY----- …`)

   The service account needs appropriate Firestore access in Google Cloud IAM,
   e.g. **Cloud Datastore User**. Use the least privilege appropriate to your deployment.

## 3. Set environment variables

On Render (Service → Environment), or in `docker-compose.yml` locally:

| Variable | Example |
|----------|---------|
| `FIREBASE_PROJECT_ID` | `cashpe-prod` |
| `FIREBASE_CLIENT_EMAIL` | `firebase-adminsdk-xxxx@cashpe-prod.iam.gserviceaccount.com` |
| `FIREBASE_PRIVATE_KEY` | whole `private_key` value; literal `\n` is fine |
| `APP_BASE_URL` | your host without scheme, e.g. `cashpe-xyz.onrender.com` |

Alternative: set `FIREBASE_SERVICE_ACCOUNT` to the full JSON (plain or base64),
or `FIREBASE_SERVICE_ACCOUNT_PATH` to a mounted `service-account.json` file.

## 4. Seed Firestore with the default data

Run once from a machine that has PHP 8 + curl, with the same env vars:

```bash
cd cashpe
export FIREBASE_PROJECT_ID=...
export FIREBASE_CLIENT_EMAIL=...
export FIREBASE_PRIVATE_KEY="..."
php tools/seed_firebase.php
# php tools/seed_firebase.php --reset   # only if you want to overwrite existing docs
```

This creates 7 collections, one per original SQL table:

`tb_partner`, `tb_transactions`, `tb_virtualtxn`, `tb_upis`, `tb_settings`,
`tb_slab`, `tb_gateway`. Each document id is the original numeric row id.

Fresh seed logins (password only, no login OTP):

| Role | Username | Password |
|------|----------|----------|
| Admin console `/Administration` | `ADMIN` | `admin123` |
| Demo merchant | `DEMO` | `demo123` |

Change both public default passwords immediately, and disable the demo account if not needed. Existing documents are skipped; existing data/passwords are not replaced. Never run `--reset` on production. Seed does not migrate original SQL records.

For both Firestore and Realtime Database rules in Hindi, see `../FIREBASE_RULES_SETUP.txt`. This application uses Firestore, not Realtime Database.

## 5. Deploy / verify

1. Push to GitHub; the root `render.yaml` builds the Docker image and sets `$PORT`.
2. Open `https://<your-app>.onrender.com/health.php`.
   It should show `"database":"firestore"`, `"status":"ok"`, and `"settings":1`.
3. Log in at `/Login` and `/Administration` with username + password only.

If health.php says `"database":"local"`, an env var is missing — check the
Render logs for `[Cashpe DB] ... Firebase authentication failed`.

## 6. Troubleshooting

| Symptom | Fix |
|---------|-----|
| `PERMISSION_DENIED` in logs | Check the service account IAM permissions (e.g. Cloud Datastore User). |
| `invalid JWT signature` | `FIREBASE_PRIVATE_KEY` newlines were stripped; paste it exactly (literal `\n` is accepted). |
| Site loads but no settings | Seed script was not run, or `APP_BASE_URL` differs — `webdata()` falls back to the first settings document. |
| Payments not confirming | Configure real Paytm/Hypto UPI credentials in Admin → Settings; status polling logic is unchanged. |
