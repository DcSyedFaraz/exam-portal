# ExamPortal

> Smart Exams. Better Results.

A full-stack Online Examination Platform built with Laravel 13, Blade, Tailwind CSS v4 (via Vite), and Spatie Laravel Permission.

## Features

- **Three Panels**: Admin, Parent, Student
- **Exam Types**: MCQ, True/False, Match Items
- **Timed Exams** with localStorage-backed countdown timer
- **Instant Results** with per-question answer breakdown
- **Exam Retakes** — only latest attempt is stored
- **Parent Dashboard** — create students, monitor progress
- **Admin Controls** — manage exams, students, view all results
- **Role-Based Access** via Spatie Laravel Permission
- **Mobile Responsive** with animated sidebar
- **Global Theming** via `resources/css/theme.css`

## Setup

### Requirements
- PHP >= 8.2
- MySQL
- Node.js >= 18
- Composer

### Installation

```bash
# 1. Install PHP dependencies
composer install

# 2. Setup environment
cp .env.example .env
php artisan key:generate

# 3. Configure your database in .env:
#    DB_CONNECTION=mysql
#    DB_DATABASE=exam_portal
#    DB_USERNAME=root
#    DB_PASSWORD=

# 4. Run migrations and seed demo data
php artisan migrate --seed

# 5. Build frontend assets
npm install
npm run build

# 6. Start the development server
php artisan serve
```

Visit: `http://127.0.0.1:8000`

## Demo Credentials

### Staff Login (email + password)

| Role    | Email              | Password |
|---------|--------------------|----------|
| Admin   | admin@exam.com     | password |
| Parent  | parent1@exam.com   | password |
| Parent  | parent2@exam.com   | password |

### Student Login (student number + PIN)

| Student Number      | PIN  |
|---------------------|------|
| (auto-generated)    | 1234 |

> To retrieve student numbers after seeding, run:
> ```bash
> php artisan tinker --execute="echo implode(PHP_EOL, App\Models\StudentProfile::pluck('student_number')->toArray());"
> ```

## Customization

### Change Platform Name / Tagline
Edit `config/app_settings.php` or set in `.env`:
```
APP_PLATFORM_NAME="My Exam Portal"
APP_TAGLINE="My Custom Tagline"
```

### Change Colors
Edit `resources/css/theme.css` — all CSS custom properties in one place.
Then rebuild: `npm run build`

## Project Structure

```
app/Http/Controllers/
├── Auth/AuthenticatedSessionController.php   # Dual login (email vs student number+PIN)
├── Admin/                                    # Admin panel controllers
├── ParentPanel/                              # Parent panel controllers
└── Student/                                  # Student panel controllers

resources/
├── css/
│   ├── theme.css          # Global color variables (edit to change theme)
│   └── app.css            # Tailwind + component classes
├── js/
│   ├── app.js             # Main entry (imports all modules)
│   ├── sidebar.js         # Mobile sidebar toggle
│   ├── exam-timer.js      # Countdown timer with localStorage + sendBeacon
│   └── confetti.js        # Confetti animation on exam pass
└── views/
    ├── layouts/           # app.blade.php, auth.blade.php
    ├── components/        # sidebar, toast
    ├── admin/             # Admin views
    ├── parent/            # Parent views
    ├── student/           # Student views
    └── auth/              # Login page

config/
└── app_settings.php       # Platform name, tagline, logo (edit here)

database/
├── migrations/            # All custom migrations
└── seeders/
    ├── RoleSeeder.php     # Roles + permissions via Spatie
    ├── UserSeeder.php     # Demo users
    └── ExamSeeder.php     # 3 sample exams (MCQ / True-False / Mixed)
```

## Tech Stack

| Layer       | Technology                        |
|-------------|-----------------------------------|
| Backend     | Laravel 13 (PHP 8.2+)             |
| Frontend    | Blade Templates + Vanilla JS      |
| Styling     | Tailwind CSS v4 via Vite          |
| Auth/Roles  | Spatie Laravel Permission         |
| Database    | MySQL                             |
