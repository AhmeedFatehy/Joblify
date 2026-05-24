<p align="center">
  <img src="https://img.shields.io/badge/Laravel-13-red?style=for-the-badge&logo=laravel" alt="Laravel 13">
  <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php" alt="PHP 8.3">
  <img src="https://img.shields.io/badge/API-JSON-blue?style=for-the-badge" alt="API JSON">
  <img src="https://img.shields.io/badge/tests-Pest-brightgreen?style=for-the-badge" alt="Pest Tests">
</p>

<h1 align="center">Joblify</h1>

<p align="center">
  <strong>API-First Job Board Platform</strong><br>
  Connecting candidates, employers, and admins through a modern, stateless JSON API.
</p>

<p align="center">
  <a href="#features">Features</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#architecture">Architecture</a> •
  <a href="#installation">Installation</a> •
  <a href="#api-documentation">API Docs</a> •
  <a href="#testing">Testing</a> •
  <a href="#license">License</a>
</p>

---

## Overview

**Joblify** is a robust, API-only job board backend built with **Laravel 13**. It is designed to serve as the engine for modern frontend applications (Vue, React, mobile apps) by providing a clean, standardized, and secure JSON API. The platform supports three distinct user roles — **Candidates**, **Employers**, and **Admins** — each with tailored workflows for job discovery, application management, and content moderation.

The project emphasizes clean architecture, strict JSON-only responses, role-based access control, comprehensive test coverage with **Pest PHP**, and developer experience through automated code linting with **Laravel Pint**.

---

## Features

### For Candidates
- **Secure Authentication:** Register and log in via email/password or social OAuth (Google, GitHub) using Laravel Sanctum.
- **Job Discovery:** Browse, search, and filter job listings by keyword, category, experience level, work type, location, and salary range.
- **Applications:** Apply to jobs with a resume and cover letter, track application status, and withdraw applications.
- **Profile Management:** Manage personal profile, upload resume, and view application history.
- **Notifications:** Receive in-app notifications for application updates and platform activity.

### For Employers
- **Company Profiles:** Create and manage company profiles with logos and descriptions.
- **Job Posting:** Post job listings with rich details including skills, categories, experience levels, and work types.
- **Application Review:** View incoming applications with candidate details, download resumes, and update application statuses (accept/reject).
- **Analytics:** Access employer-specific analytics dashboards for job performance and application metrics.
- **Comments:** Engage with the community by commenting on posts.

### For Admins
- **Moderation Dashboard:** View platform-wide activity and analytics.
- **Job Moderation:** Approve or reject pending job listings before they go live.
- **Comment Moderation:** Moderate comments to ensure community guidelines are followed.
- **User Management:** Suspend or activate user accounts.
- **Activity Logs:** Track all significant actions performed on the platform for audit purposes.

### Platform Features
- **Standardized JSON API:** Every response follows a consistent envelope structure `{ success, message, data, meta }`.
- **Role-Based Access Control (RBAC):** Custom middleware and policies enforce granular permissions.
- **File Storage:** Support for local storage or **Cloudflare R2** (S3-compatible) for resumes and company logos.
- **Email Delivery:** Transactional emails (verification, password reset, notifications) via SMTP.
- **Full-Text Search:** MySQL full-text indexing for fast and relevant job keyword search.
- **PHP Enums:** All fixed-value domains (roles, statuses, work types) use strongly-typed PHP 8.1 Enums.

---

## Tech Stack

| Category | Technology |
|----------|------------|
| **Backend Framework** | Laravel 13 |
| **Language** | PHP ^8.3 |
| **Authentication** | Laravel Sanctum (API Tokens), Laravel Socialite (OAuth) |
| **Database** | MySQL |
| **Testing** | Pest PHP 4.x |
| **Code Quality** | Laravel Pint |
| **Queue / Cache** | Database (configurable for Redis) |
| **File Storage** | Local / Cloudflare R2 (AWS S3 SDK) |
| **Mail** | SMTP |
| **Build Tool** | Vite (ready for future frontend assets) |

### Key Dependencies
- `laravel/framework: ^13.0`
- `laravel/sanctum: ^4.0`
- `laravel/socialite: ^5.27`
- `league/flysystem-aws-s3-v3: ^3.32`
- `pestphp/pest: ^4.6`
- `laravel/pint: ^1.27`

---

## Architecture

Joblify is built with an **API-First, Stateless** philosophy:

- **JSON Only:** The application never returns HTML. All exceptions are caught and rendered as JSON in `bootstrap/app.php`.
- **Token-Based Auth:** Stateless authentication using Laravel Sanctum (`Authorization: Bearer <token>`).
- **Thin Controllers:** Controllers extend a `BaseApiController` and leverage the `ApiResponse` trait for consistent output. Validation logic is extracted into dedicated Form Request classes.
- **Enforceable Policies:** Laravel Policies authorize resource ownership and actions (e.g., only an employer can update their own job posts).
- **Custom Middleware:** A `role` middleware enforces `candidate`, `employer`, and `admin` access levels at the route level.
- **Domain Enums:** PHP 8.1 Enums are used for `UserRole`, `JobStatus`, `ApplicationStatus`, `ExperienceLevel`, and `WorkType`, ensuring type safety across the codebase.

---

## Installation

### Prerequisites
- PHP `^8.3`
- Composer
- MySQL Server
- (Optional) Node.js for future frontend development

### Quick Setup

```bash
# Clone the repository
git clone https://github.com/AhmeedFatehy/Joblify.git
cd Joblify

# Run the automated setup script
composer setup
```

> The `composer setup` script installs dependencies, copies `.env.example`, generates the application key, and runs migrations.

### Manual Setup

```bash
# 1. Install PHP dependencies
composer install

# 2. Create environment file
cp .env.example .env

# 3. Generate application key
php artisan key:generate

# 4. Configure your environment
# Edit .env and set your database credentials, mail, and OAuth keys:
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=joblify
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Optional: Configure OAuth and Cloudflare R2
# GOOGLE_CLIENT_ID=...
# GOOGLE_CLIENT_SECRET=...
# GITHUB_CLIENT_ID=...
# GITHUB_CLIENT_SECRET=...
# CLOUDFLARE_R2_ACCESS_KEY_ID=...
# CLOUDFLARE_R2_SECRET_ACCESS_KEY=...

# 5. Run migrations
php artisan migrate --force

# 6. (Optional) Seed the database with sample data
php artisan db:seed
```

### Running the Application

```bash
# Start the development server
php artisan serve

# Or use the composer script
composer dev
```

The API will be available at `http://localhost:8000` by default.

---

## API Documentation

A comprehensive API reference is available in the repository:

- **[`Docs/api.md`](Docs/api.md)** — Complete endpoint documentation including authentication, jobs, applications, profiles, notifications, admin actions, and response formats.
- **[`Docs/schema.txt`](Docs/schema.txt)** — Database schema overview.
- **[`Docs/user_stories.txt`](Docs/user_stories.txt)** — Product requirements and user stories.

### Authentication

All protected endpoints require a `Bearer` token in the `Authorization` header:

```http
Authorization: Bearer <your-sanctum-token>
```

### Example Response Format

```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": { ... },
  "meta": { "current_page": 1, "last_page": 5 }
}
```

---

## Testing

The project uses **Pest PHP** for expressive, clean testing.

```bash
# Run the full test suite
composer test

# Run tests directly
./vendor/bin/pest

# Or via Artisan
php artisan test
```

### Code Quality

```bash
# Fix code style automatically
composer lint

# Check code style (CI mode)
composer lint:check
```

---

## Project Structure

```
Joblify/
├── app/
│   ├── Enums/              # PHP 8.1 Enums (Roles, Statuses, etc.)
│   ├── Http/
│   │   ├── Controllers/Api/  # All API controllers
│   │   ├── Requests/         # Form Request validation classes
│   │   └── Middleware/       # Custom middleware (e.g., CheckRole)
│   ├── Models/             # Eloquent models with relations
│   ├── Policies/           # Authorization policies
│   ├── Services/           # Business logic services
│   ├── Actions/            # Domain action classes
│   └── Traits/
│       └── ApiResponse.php # Standardized JSON response helpers
├── database/
│   ├── migrations/         # 28+ migration files
│   ├── factories/          # Model factories for testing
│   └── seeders/            # Database seeders
├── routes/
│   ├── api.php             # All API endpoints
│   └── web.php             # Health checks and named routes for tests
├── tests/
│   ├── Feature/            # Endpoint tests (Auth, Admin, Employer, Jobs, etc.)
│   ├── Unit/               # Unit tests
│   └── Pest.php            # Pest configuration
├── Docs/
│   ├── api.md              # Full API documentation
│   ├── schema.txt          # Database schema
│   └── user_stories.txt    # Product user stories
├── composer.json
├── phpunit.xml
├── pint.json
└── .env.example
```

---

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

---

<p align="center">
  Built with precision using <strong>Laravel</strong> & <strong>Pest</strong>.
</p>
