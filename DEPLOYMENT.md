# 🌐 Deploying EduCoach for free

This guide shows how to put EduCoach back online — **the app *and* its database — at no cost.**

Because the app is now fully **environment-driven** and **Dockerised**, you can host it almost
anywhere. Below are three routes, from the most modern to the most familiar.

| Option | App host | Database | Best for | Truly free? |
|--------|----------|----------|----------|-------------|
| **1. Render + TiDB** ⭐ | Render (Docker) | TiDB Cloud Serverless | Modern, auto-deploy from GitHub | ✅ Yes (no card) |
| **2. InfinityFree** | InfinityFree | InfinityFree MySQL | Familiar all-in-one cPanel | ✅ Yes (no card) |
| **3. Railway** | Railway | Railway MySQL | Easiest developer experience | ⚠️ Free trial credits |

> ℹ️ Why not the host you used before? InfinityFree (the likely original host) **deletes free
> accounts after a period of inactivity** — that's almost certainly what happened. Option 1
> avoids this and gives you Git-based auto-deploys.

---

## ⭐ Option 1 — Render (app) + TiDB Cloud (database) — recommended

A modern, genuinely-free setup: push to GitHub, Render builds your `Dockerfile` automatically,
and TiDB Cloud provides a free, persistent, MySQL-compatible database.

> **Trade-off:** Render's free web service "sleeps" after ~15 minutes of inactivity, so the
> first request after idle takes ~30–60s to wake. Perfect for a portfolio/demo site.

### Step 1 — Create the database (TiDB Cloud Serverless)

1. Sign up at **<https://tidbcloud.com>** (free, no credit card) and create a **Serverless** cluster.
2. Click **Connect** and note the connection details. TiDB uses:
   - **Host:** `gateway01.<region>.prod.aws.tidbcloud.com`
   - **Port:** `4000`  *(not 3306!)*
   - **User:** `xxxxxxxx.root`  *(note the prefix)*
   - **Password:** *(the one you set)*
   - **TLS:** required
3. Create your database and import the schema. From the **SQL Editor** in the TiDB console,
   or with the MySQL client locally:
   ```bash
   mysql --host gateway01.<region>.prod.aws.tidbcloud.com --port 4000 \
         -u 'xxxxxxxx.root' -p --ssl-mode=VERIFY_IDENTITY \
         -e "CREATE DATABASE cs4116_marketplace CHARACTER SET utf8mb4;"

   mysql --host ... --port 4000 -u 'xxxxxxxx.root' -p --ssl-mode=VERIFY_IDENTITY \
         cs4116_marketplace < database/schema.sql
   mysql --host ... --port 4000 -u 'xxxxxxxx.root' -p --ssl-mode=VERIFY_IDENTITY \
         cs4116_marketplace < database/initial_data.sql
   ```

> 💡 Prefer plain MySQL? **Aiven** (<https://aiven.io>) and **Clever Cloud** also offer free
> MySQL plans that use the normal port `3306`. The app supports them identically — just set
> `DB_SSL=true` and the matching host/port.

### Step 2 — Push your code to GitHub

```bash
git add .
git commit -m "Modernise EduCoach and add Docker deployment"
git push
```

### Step 3 — Create the web service on Render

1. Sign up at **<https://render.com>** and click **New → Web Service**.
2. Connect your GitHub repo. Render auto-detects the **Dockerfile**.
3. Choose the **Free** instance type.
4. Add these **Environment Variables** (Settings → Environment):

   | Key          | Value                                              |
   |--------------|----------------------------------------------------|
   | `APP_ENV`    | `production`                                        |
   | `DB_HOST`    | `gateway01.<region>.prod.aws.tidbcloud.com`         |
   | `DB_PORT`    | `4000`                                              |
   | `DB_NAME`    | `cs4116_marketplace`                                |
   | `DB_USER`    | `xxxxxxxx.root`                                     |
   | `DB_PASS`    | *(your TiDB password)*                              |
   | `DB_SSL`     | `true`                                              |
   | `DB_SSL_CA`  | `/etc/ssl/certs/ca-certificates.crt`                |

   *(`DB_SSL_CA` points at the CA bundle already present in the Docker image.)*

5. Click **Create Web Service**. Render builds the image and deploys. Your site goes live at
   `https://<your-app>.onrender.com`. Every `git push` redeploys automatically.

---

## Option 2 — InfinityFree (classic all-in-one, no card)

Closest to the original setup: free PHP 8 + MySQL with a cPanel-style dashboard.
No Docker/SSH, so you upload files directly.

1. Create an account at **<https://infinityfree.com>** and a new free hosting account.
2. In the control panel, create a **MySQL database** and note its host, name, user and password.
3. Import the schema via **phpMyAdmin**:
   - Open phpMyAdmin → select your database → **Import** → upload `database/schema.sql`,
     then repeat for `database/initial_data.sql`.
4. Upload the project files to **`htdocs/`** via the File Manager or FTP.
5. Create a **`.env`** file in the site root with your database details:
   ```
   APP_ENV=production
   DB_HOST=sqlXXX.infinityfree.com
   DB_NAME=if0_xxxxxxx_cs4116_marketplace
   DB_USER=if0_xxxxxxx
   DB_PASS=your_password
   DB_PORT=3306
   ```
6. Visit your subdomain — done.

> ⚠️ **Avoid the inactivity deletion** that lost your last deployment: log in to the
> InfinityFree dashboard at least once a month, or set a calendar reminder.

---

## Option 3 — Railway (best developer experience)

Railway gives the smoothest workflow but runs on **trial credits** (~$5/month of usage),
so it's "free-ish" rather than free-forever.

1. Sign up at **<https://railway.app>** and create a **New Project → Deploy from GitHub repo**.
   Railway builds the `Dockerfile` automatically.
2. In the same project, click **New → Database → MySQL**.
3. Open the MySQL service → **Connect** tab, and copy the connection variables.
4. In the **app** service → **Variables**, add `APP_ENV=production` plus the DB variables
   (you can reference Railway's `MYSQLHOST`, `MYSQLPORT`, etc., or set `DATABASE_URL`).
5. Import the schema using Railway's database connection string:
   ```bash
   mysql -h <host> -P <port> -u <user> -p <db> < database/schema.sql
   mysql -h <host> -P <port> -u <user> -p <db> < database/initial_data.sql
   ```

---

## ✅ Post-deployment checklist

- [ ] Set `APP_ENV=production` (hides PHP errors from visitors)
- [ ] **Change the demo passwords** — especially `admin@educoach.com`
- [ ] Confirm the database imported (try logging in)
- [ ] Verify HTTPS is on (Render/Railway give it automatically)
- [ ] Never commit your real `.env` (it's already in `.gitignore`)

---

## 🧭 Quick decision guide

- **Want it modern, free, and auto-deploying from GitHub?** → **Option 1 (Render + TiDB)**
- **Want the simplest thing closest to before?** → **Option 2 (InfinityFree)**
- **Want the nicest dev workflow and don't mind trial credits?** → **Option 3 (Railway)**
