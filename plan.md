# WatchList Reminder Web Application - Development Plan

## 1. Project Description and Goals
Build a full-featured WatchList Reminder Application where users can:
- Register and login with their own accounts.
- Add dramas, movies, anime, TV series, and any watchable content to their personal watchlist.
- Auto-fetch content details (poster, description, episodes, air dates) from free APIs.
- Set smart episode schedule patterns (daily, weekly, bi-weekly, custom, etc.).
- Receive email (SMTP) and SMS reminders before new episodes air.
- Manage all notification preferences from their dashboard.
- View notification history and upcoming episode schedule.

### Tech Stack
- **Framework:** Laravel 13
- **PHP:** 8.3+
- **Database:** MySQL 8.0+
- **Frontend:** Blade templates + Alpine.js + Livewire 3 + TailwindCSS 3
- **Auth:** Laravel Breeze (Blade stack)
- **Mail:** SMTP via Laravel Mail (config via .env — NOT php mail())
- **SMS:** Custom HTTP gateway integration (ViserLab SMSLab PHP script via cURL POST)
- **Queue/Jobs:** Laravel Queue (database driver, upgradeable to Redis)
- **Scheduler:** Laravel Task Scheduler (cron every 15 minutes)

---

## 2. Complete Database Schema

### `users`
- `id` (PK)
- `name` (string)
- `email` (string, unique)
- `password` (string)
- `phone` (string, nullable)
- `timezone` (string)
- `avatar` (string, nullable)
- `email_notifications` (boolean, default true)
- `sms_notifications` (boolean, default false)
- `sms_gateway_enabled` (boolean, default false)
- `remember_token` (string)
- `timestamps`

### `shows`
- `id` (PK)
- `user_id` (FK to users)
- `title` (string)
- `slug` (string)
- `type` (enum: drama, movie, anime, tv_series, anime_movie, other)
- `description` (text, nullable)
- `poster_url` (string, nullable)
- `backdrop_url` (string, nullable)
- `tmdb_id` (string/int, nullable)
- `jikan_id` (string/int, nullable)
- `imdb_id` (string, nullable)
- `status` (enum: watching, completed, on_hold, dropped, plan_to_watch)
- `country` (string, nullable)
- `language` (string, nullable)
- `total_episodes` (int, nullable)
- `genres` (json, nullable)
- `rating` (string/float, nullable)
- `year` (string/int, nullable)
- `timestamps`
- `soft_deletes`

### `episodes`
- `id` (PK)
- `show_id` (FK to shows)
- `season_no` (int, nullable)
- `episode_no` (int)
- `title` (string, nullable)
- `description` (text, nullable)
- `air_datetime` (datetime)
- `duration_minutes` (int, nullable)
- `thumbnail_url` (string, nullable)
- `youtube_link` (string, nullable)
- `is_aired` (boolean, default false)
- `notified` (boolean, default false)
- `timestamps`

### `schedules`
- `id` (PK)
- `show_id` (FK to shows)
- `pattern` (enum: daily, weekly, bi_weekly, twice_per_week, monthly, irregular, movie_one_time)
- `days_of_week` (json, nullable — e.g. ["monday","friday"])
- `air_time` (time)
- `timezone` (string)
- `episodes_per_slot` (int, default 1)
- `start_date` (date)
- `end_date` (date, nullable)
- `is_active` (boolean, default true)
- `timestamps`

### `reminders`
- `id` (PK)
- `user_id` (FK to users)
- `show_id` (FK to shows)
- `episode_id` (FK to episodes, nullable)
- `remind_before_minutes` (int — e.g. 30, 60, 1440 for 1 day)
- `channels` (json — ["email","sms"])
- `is_active` (boolean, default true)
- `timestamps`

### `notification_logs`
- `id` (PK)
- `user_id` (FK to users)
- `show_id` (FK to shows)
- `episode_id` (FK to episodes)
- `channel` (enum: email, sms)
- `status` (enum: sent, failed, pending)
- `message` (text)
- `error_message` (text, nullable)
- `sent_at` (datetime, nullable)
- `timestamps`

### `user_smtp_settings`
- `id` (PK)
- `user_id` (FK to users, unique)
- `host` (string)
- `port` (int)
- `username` (string)
- `password` (string, encrypted)
- `encryption` (enum: tls, ssl, none)
- `from_address` (string)
- `from_name` (string)
- `is_active` (boolean, default true)
- `tested_at` (datetime, nullable)
- `timestamps`

### `user_sms_settings`
- `id` (PK)
- `user_id` (FK to users, unique)
- `gateway_url` (string)
- `api_key` (string, encrypted, nullable)
- `sender_id` (string, nullable)
- `extra_params` (json, nullable — for ViserLab SMSLab params)
- `is_active` (boolean, default true)
- `tested_at` (datetime, nullable)
- `timestamps`

### `watchlist_notes`
- `id` (PK)
- `user_id` (FK to users)
- `show_id` (FK to shows)
- `note` (text)
- `timestamps`

---

## 3. API Integrations

All API integrations are free to use.

1. **TMDB API:**
   - Used for fetching Movies, TV Series, and Dramas.
   - Requires API key (stored in `.env`).
   - Responses are cached for 24 hours.
   - Endpoint examples: `/search/multi`, `/movie/{id}`, `/tv/{id}`.
2. **Jikan API v4:**
   - Used for Anime content discovery.
   - Completely free, no API key required.
   - Responses cached for 24 hours.
   - Endpoint examples: `/anime`, `/anime/{id}/episodes`.
3. **YouTube oEmbed API:**
   - Base URL: `https://www.youtube.com/oembed`
   - Used to extract title and thumbnail from pasted YouTube links.
   - No key required.

External calls will use a dedicated service class (e.g., `TmdbService`, `JikanService`, `YoutubeService`) and will be enclosed in try/catch blocks to log any errors gracefully.

---

## 4. Notification System Architecture

- **Scheduled Checks:** A Laravel console command (`CheckUpcomingEpisodes`) runs every 15 minutes to find all episodes with `is_aired = false`, `notified = false`, and whose `air_datetime` is approaching (offset by the `remind_before_minutes` buffer).
- **Queued Execution:** For each candidate episode, the command dispatches a `SendEpisodeReminderJob` to the database queue.
- **Job Processing:**
  - The job inspects the required channels for a specific user.
  - **Email:** The job first looks up `user_smtp_settings`. If available, sets credentials at runtime. Otherwise falls back to system defaults. Dispatches an HTML Blade email (`EpisodeReminderMail`).
  - **Logging:** Any success or failure from either channel is logged inside `notification_logs`.

---

## 5. SMS Gateway Integration

- Relies on custom HTTP gateway (ViserLab SMSLab PHP script).
- Setup via single REST POST call via cURL.
- Model `UserSmsSetting` holds `gateway_url`, `api_key`, `sender_id`, and `extra_params` (JSON for the specific payload requirements).
- Keys are stored encrypted using Laravel's `Crypt`.
- `SmsService::send(User $user, string $message)` constructs the post request, handles the payload mapping, executes it, and returns true/false based on response, avoiding synchronous halts during execution.

---

## 6. The 10 Development Phases

### PHASE 1 — Project Setup & Configuration
- **Goal:** Initialize Laravel application and configure base architecture.
- **Files Created/Modified:** `composer.json`, `.env`, `resources/views/layouts/app.blade.php`.
- **Artisan Commands:** `composer create-project laravel/laravel`, `composer require laravel/breeze --dev`, `php artisan breeze:install blade`, `npm install`, `npm run build`.
- **Instructions:** Install Laravel 13, set `.env` properly (DB, Queue=database), install Livewire, Alpine, Tailwind, Breeze. Create a dark-mode base UI shell with nav.
- **.env Needs:** `DB_*`, `APP_*`, `QUEUE_CONNECTION=database`.
- **Expected Outcome:** Auth pages work, dashboard is visible, styling applied.

### PHASE 2 — Database Schema & Models
- **Goal:** Set up all database tables and Eloquent models with relations.
- **Files Created/Modified:** Migrations, Models (`User`, `Show`, `Episode`, `Schedule`, `Reminder`, `NotificationLog`, `UserSmtpSetting`, `UserSmsSetting`, `WatchlistNote`).
- **Artisan Commands:** `php artisan make:model Show -m`, `php artisan make:model Episode -m`, etc., `php artisan migrate`.
- **Instructions:** Define all schemas with appropriate column types, JSON casts, and enums. Set up relationships (BelongsTo, HasMany) explicitly.
- **.env Needs:** None.
- **Expected Outcome:** Database tables reflect the full schema; Models are ready for queries.

### PHASE 3 — Authentication & User Profile
- **Goal:** Extend Breeze with custom user profile elements.
- **Files Created/Modified:** `routes/web.php`, `app/Http/Controllers/ProfileController.php`, `resources/views/profile/*.blade.php`.
- **Artisan Commands:** `php artisan storage:link`.
- **Instructions:** Add phone, timezone selector to user profile. Allow avatar upload using `storage/app/public/avatars`. Add timezone lists via PHP.
- **.env Needs:** `APP_URL` matching the domain for storage links.
- **Expected Outcome:** Profile page works natively, updates timezone, stores avatar successfully.

### PHASE 4 — Notification Settings (SMTP + SMS)
- **Goal:** Enable users to add custom SMTP and SMS configs.
- **Files Created/Modified:** Livewire components (`ManageSmtp`, `ManageSms`, `ManageReminders`), `resources/views/livewire/*.blade.php`.
- **Artisan Commands:** `php artisan make:livewire ManageSmtp`, `php artisan make:livewire ManageSms`.
- **Instructions:** Build notification settings form using Livewire. Encrypt `.env` passwords (`Crypt::encrypt`). Create test email/SMS functionalities to verify the entered endpoints work flawlessly.
- **.env Needs:** General `MAIL_*` defaults.
- **Expected Outcome:** User can explicitly toggle email and SMS functionality based on correct credentials.

### PHASE 5 — Content Search & Auto-Fetch System
- **Goal:** Allow users to search the web (TMDB/Jikan) or use YouTube endpoints for their specific content.
- **Files Created/Modified:** `App\Services\TmdbService`, `App\Services\JikanService`, `App\Services\YoutubeService`, Livewire components (`SearchContent`).
- **Artisan Commands:** `php artisan make:livewire SearchContent`.
- **Instructions:** Implement Livewire real-time query interface with tabs (Search Online, Paste Link, Manual). Query external APIs and format standard responses, caching them.
- **.env Needs:** `TMDB_API_KEY`, `TMDB_BASE_URL`, `TMDB_IMAGE_BASE_URL`, `JIKAN_BASE_URL`.
- **Expected Outcome:** Adding content to a watchlist fetches standard details efficiently.

### PHASE 6 — Episode Scheduler & Smart Schedule Engine
- **Goal:** Plan and forecast episode releases.
- **Files Created/Modified:** `App\Services\ScheduleEngine.php`, Livewire component (`ScheduleSetup`), `resources/views/shows/schedule.blade.php`.
- **Instructions:** Display scheduler logic when a Show is added to Watchlist. Auto-generate subsequent `Episode` dates directly using timezone translation and duration math. Store inside `episodes`. Create UI to override user settings per-show.
- **.env Needs:** None.
- **Expected Outcome:** Episodes magically populate relative to the chosen cadence.

### PHASE 7 — Reminder Engine & Notification Dispatch
- **Goal:** Process logic to send the email/SMS effectively based on deadlines.
- **Files Created/Modified:** `routes/console.php`, `App\Jobs\SendEpisodeReminderJob`, `App\Mail\EpisodeReminderMail`, `App\Services\SmsService`, views in `resources/views/emails/`.
- **Artisan Commands:** `php artisan make:job SendEpisodeReminderJob`, `php artisan make:mail EpisodeReminderMail`.
- **Instructions:** Populate `routes/console.php` with a 15 min scheduled check. Fetch correct data, pass context to queued job. Set runtime email drivers where needed. Dispatch to cURL logic for SMS. Check logs.
- **.env Needs:** None.
- **Expected Outcome:** Emails and SMS dispatch reliably using cron queues.

### PHASE 8 — Dashboard & Watchlist UI
- **Goal:** Create user-friendly displays for all the generated data points.
- **Files Created/Modified:** Livewire components (`WatchlistGrid`, `UpcomingEpisodes`), controllers, `resources/views/dashboard.blade.php`, `resources/views/watchlist/*.blade.php`.
- **Instructions:** Make dashboard panels (Airing today, airing this week). Show the Watchlist and detailed UI for specific instances. Enable 'Mark Watched' manually. Enable Pagination.
- **.env Needs:** None.
- **Expected Outcome:** Functional core UI mapping all logic together.

### PHASE 9 — Advanced Features
- **Goal:** Final polish and 'power' modules.
- **Files Created/Modified:** `routes/web.php`, `resources/views/admin/*.blade.php`, JSON Importer controllers.
- **Instructions:** Support simple JSON parsing from MAL to add Anime. Query simple recommendations. Construct the basic Admin view to observe `notification_logs`. Complete the toggleable Dark Mode switch to store status natively.
- **.env Needs:** `ADMIN_EMAIL`.
- **Expected Outcome:** Deep feature set completeness.

### PHASE 10 — Production Deployment & Optimization
- **Goal:** Set up for Cloudpanel deployment.
- **Instructions:** `php artisan optimize`, `php artisan config:cache` implementations. Prepare the README with explicit setup, cron hooks execution path, Supervisor queue rules. Establish CSRF, XSS expectations are resolved inherently.
- **.env Needs:** `APP_ENV=production`, `APP_DEBUG=false`.
- **Expected Outcome:** Solid optimized build file configuration ready.

---

## 7. .ENV Variables Reference

```dotenv
APP_NAME="WatchList Reminder"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=watchlist_db
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
QUEUE_CONNECTION=database
SESSION_DRIVER=file

# Default SMTP (fallback if user has no custom SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# TMDB API
TMDB_API_KEY=
TMDB_BASE_URL=https://api.themoviedb.org/3
TMDB_IMAGE_BASE_URL=https://image.tmdb.org/t/p/w500

# Jikan API (no key needed)
JIKAN_BASE_URL=https://api.jikan.moe/v4

# Admin
ADMIN_EMAIL=admin@yourdomain.com
```
