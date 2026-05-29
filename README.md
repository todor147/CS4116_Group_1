# 🎓 EduCoach — Educational Coaching Marketplace

EduCoach is a web marketplace that connects **learners** with expert **coaches and tutors**
across subjects like maths, languages, sciences, music and coding. Learners can search,
message, book sessions and leave reviews; coaches can advertise services, set pricing tiers
and manage their availability.

Originally built for the **CS4116** module at the University of Limerick, the project has been
**fully modernised**: a clean environment-driven configuration, a single PDO database layer,
a refreshed Bootstrap 5.3 design system, CSRF-protected authentication, and a one-command
Docker setup so it runs anywhere.

> **Tech stack:** PHP 8.2 · MySQL / MariaDB · Bootstrap 5.3 · vanilla JS · Apache · Docker

---

## ✨ Features

- **Accounts & roles** — learners, coaches (business) and admins, with secure registration/login
- **Coach profiles** — headline, bio, skills, availability, intro video and pricing tiers
- **Search & filtering** — by subject, category, price and rating
- **Service inquiries & sessions** — request → accept → schedule → complete → review
- **Messaging** — direct learner ↔ coach messages, plus peer "customer insight" requests
- **Reviews & ratings** — verified reviews from completed sessions, with coach responses
- **Notifications** — in-app notifications with live unread badge
- **Admin panel** — user management, moderation and banned-words filtering

---

## 🚀 Quick start

### Option A — Docker (recommended, zero local setup)

Requires [Docker Desktop](https://www.docker.com/products/docker-desktop/).

```bash
docker compose up --build
```

Then open:

| Service  | URL                     | Notes                                   |
|----------|-------------------------|-----------------------------------------|
| App      | http://localhost:8080   | The website                             |
| Adminer  | http://localhost:8081   | DB GUI — server `db`, user `root`, pw `root` |

The database schema and sample data are imported **automatically** on first run.

### Option B — XAMPP / local PHP + MySQL

1. Copy the env template and adjust if needed:
   ```bash
   cp .env.example .env
   ```
2. Create the database and import the schema + sample data:
   ```sql
   CREATE DATABASE cs4116_marketplace CHARACTER SET utf8mb4;
   ```
   ```bash
   mysql -u root cs4116_marketplace < database/schema.sql
   mysql -u root cs4116_marketplace < database/initial_data.sql
   ```
3. Point your web root at the project folder (or run the built-in server):
   ```bash
   php -S localhost:8000
   ```
4. Visit http://localhost:8000

---

## 🔑 Demo accounts

All sample accounts use the password **`Password123!`**.

| Role    | Email                   |
|---------|-------------------------|
| Admin   | `admin@educoach.com`    |
| Coach   | `john@example.com`      |
| Learner | `student1@example.com`  |

> ⚠️ **Change these before any public deployment.** They exist only for local demos.

---

## ⚙️ Configuration

All configuration is read from environment variables (or a local `.env` file). Real host
environment variables always win, so the same code runs locally and in production.

| Variable        | Default               | Purpose                                   |
|-----------------|-----------------------|-------------------------------------------|
| `APP_ENV`       | `development`         | `production` hides errors from visitors   |
| `APP_TIMEZONE`  | `Europe/Dublin`       | Default timezone                          |
| `DB_HOST`       | `localhost`           | Database host                             |
| `DB_PORT`       | `3306`                | Database port                             |
| `DB_NAME`       | `cs4116_marketplace`  | Database name                             |
| `DB_USER`       | `root`                | Database user                             |
| `DB_PASS`       | *(empty)*             | Database password                         |
| `DATABASE_URL`  | —                     | Optional single connection string (overrides `DB_*`) |
| `DB_SSL`        | `false`              | Enable TLS (required by some cloud DBs)   |
| `DB_SSL_CA`     | —                     | Path to a CA bundle for strict TLS        |

See [`.env.example`](.env.example) for the full template.

---

## 🌐 Deploying for free

Want it back online for free (app **and** database)? See **[DEPLOYMENT.md](DEPLOYMENT.md)**
for step-by-step guides covering:

- **Render + TiDB Cloud** — modern, GitHub-auto-deploy, truly free *(recommended)*
- **InfinityFree** — classic all-in-one free PHP + MySQL host
- **Railway** — easiest developer experience

---

## 📁 Project structure

```
.
├── index.php                 # Entry point → redirects to pages/home.php
├── includes/                 # Shared back-end code
│   ├── config.php            # Bootstrap: env, sessions, security headers, helpers
│   ├── db_connection.php     # Single PDO connection (env-driven)
│   ├── header.php / footer.php  # Shared layout (modernised)
│   ├── auth_functions.php    # Authentication helpers
│   └── ...
├── pages/                    # All user-facing pages
├── assets/                   # css / js / images
├── database/                 # schema.sql + initial_data.sql
├── docker/                   # Apache vhost + entrypoint
├── Dockerfile
└── docker-compose.yml
```

The authoritative database schema lives in [`database/schema.sql`](database/schema.sql).

---

## 🔒 Security notes

- Passwords hashed with PHP `password_hash()` (bcrypt)
- All queries use **prepared statements** (PDO)
- **CSRF tokens** on authentication forms
- Secure session cookies (`HttpOnly`, `SameSite=Lax`, `Secure` over HTTPS)
- Security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`)
- Secrets kept out of source control via `.env` (never commit it)

---

## 📜 License & credits

Built by CS4116 Group 1 (University of Limerick) under the supervision of Professor Conor Ryan.
For educational use.
