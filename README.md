# ReferHarmony

**Your Healthcare Journey, Simplified**

ReferHarmony is a patient-facing healthcare management platform designed to help patients navigate their healthcare journey with clarity and ease. The MVP focuses on providing patients with tools to manage appointments, track tasks, and discover relevant community health events.

---

## Features

### Patient Portal
- ✅ **Dashboard** - View upcoming appointments, pending tasks, and health summary
- ✅ **Appointment Management** - Create, view, edit, and delete appointments
- ✅ **Document Management** - Upload and manage appointment documents
- ✅ **Task Tracking** - Create and track healthcare tasks with completion status
- ✅ **Encounter Timeline** - View complete healthcare history in chronological order
- ✅ **AI-Generated Summaries** - Plain English health record and executive summary
- ✅ **Community Events Feed** - Discover relevant health events based on your interests
- ✅ **Provider Scheduling** - Schedule appointments with healthcare providers based on availability and location

### Technical Stack
- **Framework:** Laravel 12
- **Frontend:** Livewire Volt + Flux UI + TailwindCSS v4
- **Database:** SQLite (MySQL/PostgreSQL supported)
- **Authentication:** Laravel Fortify
- **AI Integration:** OpenAI for health summaries
- **Document Generation:** DomPDF
- **Testing:** Pest (142 tests, 408 assertions)

---

## Quick Start

### Prerequisites
- PHP 8.4+
- Composer
- Node.js 18+ and npm
- SQLite (included with PHP)

### Installation

```bash
# Clone repository
git clone <repository-url>
cd referharmony

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Create and migrate database
touch database/database.sqlite
php artisan migrate

# Seed sample data (optional)
php artisan db:seed

# Build frontend assets
npm run build

# Create storage symlink
php artisan storage:link

# Start development server
php artisan serve
```

Visit http://localhost:8000 and register a new account to get started!

### Generate Sample Healthcare Data (Optional)

To test with realistic healthcare encounters:

1. Add OpenAI API key to `.env`:
   ```
   OPENAI_API_KEY=sk-your-key-here
   ```

2. Generate sample encounter:
   ```bash
   php artisan mock:healthcare-encounter {patient_id}
   ```

This creates a complete healthcare visit with:
- Past appointment with visit notes
- Professional PDF visit summary
- Referral tasks
- Follow-up appointment
- AI-generated Plain English Patient Record
- AI-generated Executive Summary

---

## Documentation

### Getting Started
- **[Quick Start Guide](QUICKSTART.md)** - Get up and running in minutes
- **[MVP Plan](docs/mvp-plan.md)** - Complete feature specifications and roadmap

### Deployment
- **[Deployment Guide](docs/DEPLOYMENT.md)** - Comprehensive production deployment instructions
- **[Production Checklist](docs/PRODUCTION-CHECKLIST.md)** - Step-by-step deployment verification

### Development
- **[CLAUDE.md](CLAUDE.md)** - Project instructions and coding guidelines
- **[Tests](tests/)** - 142 passing tests covering all major features

---

## Testing

### Run All Tests
```bash
php artisan test
```

Expected: **142 tests passing, 408 assertions**

### Run Specific Tests
```bash
# Feature tests only
php artisan test --testsuite=Feature

# Specific test file
php artisan test tests/Feature/DashboardTest.php

# Filter by name
php artisan test --filter=appointment
```

### Code Quality
```bash
# Run Laravel Pint formatter
vendor/bin/pint

# Check without formatting
vendor/bin/pint --test
```

---

## Development Commands

### Common Tasks
```bash
# Start dev server
php artisan serve

# Watch frontend changes
npm run dev

# Build for production
npm run build

# Clear caches
php artisan optimize:clear

# View routes
php artisan route:list

# Interactive shell
php artisan tinker
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migration
php artisan migrate:rollback

# Fresh database with seed data
php artisan migrate:fresh --seed
```

---

## Project Structure

```
referharmony/
├── app/
│   ├── Console/Commands/     # Artisan commands
│   ├── Http/
│   │   ├── Controllers/      # Route controllers
│   │   └── Requests/         # Form requests
│   ├── Models/               # Eloquent models
│   ├── Policies/             # Authorization policies
│   └── Services/             # Business logic
├── database/
│   ├── factories/            # Model factories
│   ├── migrations/           # Database migrations
│   └── seeders/              # Database seeders
├── docs/                     # Documentation
├── resources/
│   └── views/
│       └── livewire/         # Livewire Volt components
├── routes/
│   └── web.php               # Web routes
└── tests/
    ├── Feature/              # Feature tests
    └── Unit/                 # Unit tests
```

---

## Key Features Explained

### Dashboard
The patient dashboard provides a comprehensive overview:
- **Upcoming Appointments** (next 3) with distance from patient location
- **Pending Tasks** with scheduling workflow for referral tasks
- **Executive Summary** - AI-generated health status (clickable, links to timeline)
- **Community Events Feed** - Filtered based on patient health interests

### Appointment Management
Appointments serve dual purposes:
- **Future Appointments** - Upcoming scheduled visits
- **Past Appointments** - Healthcare encounters with visit summaries and documents

### Task Management
Tasks track follow-up actions:
- Regular tasks (e.g., "Call insurance about coverage")
- **Scheduling tasks** (e.g., "Schedule Cardiology appointment")
  - Triggers provider selection workflow
  - Shows providers filtered by specialty
  - Displays distance from patient
  - Shows availability for preferred system providers
  - Creates appointment when time slot selected

### Timeline (Health History)
View complete healthcare journey:
- Plain English Patient Record (AI-generated from all encounters)
- Executive Summary (most recent health status)
- Chronological list of all past appointments
- Visit summaries, documents, and related tasks

### AI Features
Powered by OpenAI:
- **Plain English Patient Record** - Comprehensive health history in understandable language
- **Executive Summary** - Current health status summary
- Auto-updates when new encounters are added

---

## Security

### Built-in Protections
- ✅ CSRF protection on all forms
- ✅ XSS prevention via Blade escaping
- ✅ SQL injection prevention via Eloquent
- ✅ File upload validation (type, size)
- ✅ Authorization policies (users can only access their own data)
- ✅ Rate limiting on authentication routes
- ✅ Password hashing with bcrypt

### Production Requirements
- Set `APP_DEBUG=false`
- Set `APP_ENV=production`
- Enable HTTPS
- Configure proper CORS settings
- Use strong database passwords
- Store `.env` securely (never commit to version control)

---

## Deployment

### Laravel Cloud (Recommended)

1. **Prepare environment variables** (see [Deployment Guide](docs/DEPLOYMENT.md))
2. **Configure build command:** `npm ci && npm run build`
3. **Configure deploy command:** `php artisan migrate --force && php artisan storage:link`
4. **Deploy** and verify

See the [Deployment Guide](docs/DEPLOYMENT.md) for detailed instructions.

### Production Checklist

Before deploying:
- [ ] All tests passing
- [ ] Code formatted with Pint
- [ ] Frontend built (`npm run build`)
- [ ] Environment variables configured
- [ ] Email service configured
- [ ] File storage configured (S3 recommended)
- [ ] Error monitoring set up
- [ ] Backups configured

See the [Production Checklist](docs/PRODUCTION-CHECKLIST.md) for complete verification steps.

---

## Milestones Completed

All 13 MVP milestones completed:

1. ✅ Foundation Setup - Laravel 12, authentication, basic layout
2. ✅ Database Schema & Models - All tables and relationships
3. ✅ Patient Record Creation - Auto-creation on registration
4. ✅ Dashboard - Appointments, tasks, executive summary, events feed
5. ✅ Mock Healthcare Data Command - AI-powered encounter generation
6. ✅ Appointment Manager (List & Create) - CRUD operations
7. ✅ Mock Scheduling Workflow - Provider selection and appointment scheduling
8. ✅ Appointment Manager (View, Edit, Delete) - Complete CRUD with task management
9. ✅ Task Manager - Full task CRUD with appointment linking
10. ✅ Encounter Timeline - Healthcare history with AI summaries
11. ✅ Event Details & Feed - Community events with filtering
12. ✅ UI/UX Polish & Mobile Optimization - Responsive, polished UI
13. ✅ Testing & Code Quality - 142 tests, formatted code
14. ✅ Deployment Preparation - Documentation and production-ready configuration

---

## Contributing

### Code Standards
- Follow Laravel conventions
- Use Livewire Volt for interactive components
- Use Flux UI components consistently
- Run Pint before committing (`vendor/bin/pint`)
- Write tests for new features
- Use descriptive commit messages

### Testing Standards
- Feature tests for all user-facing functionality
- Unit tests for business logic
- Factories for test data
- Maintain 80%+ code coverage

---

## Support

### Documentation
- Quick Start: [QUICKSTART.md](QUICKSTART.md)
- MVP Plan: [docs/mvp-plan.md](docs/mvp-plan.md)
- Deployment: [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md)
- Production Checklist: [docs/PRODUCTION-CHECKLIST.md](docs/PRODUCTION-CHECKLIST.md)

### External Resources
- Laravel 12: https://laravel.com/docs/12.x
- Livewire: https://livewire.laravel.com
- Flux UI: https://fluxui.dev
- Laravel Cloud: https://cloud.laravel.com/docs

### Troubleshooting
Check application logs: `storage/logs/laravel.log`

---

## License

This project is proprietary software. All rights reserved.

---

## Credits

Built with:
- [Laravel](https://laravel.com) - PHP Framework
- [Livewire](https://livewire.laravel.com) - Dynamic UI
- [Flux UI](https://fluxui.dev) - UI Components
- [TailwindCSS](https://tailwindcss.com) - CSS Framework
- [Laravel Fortify](https://laravel.com/docs/12.x/fortify) - Authentication
- [OpenAI](https://openai.com) - AI Summaries
- [DomPDF](https://github.com/barryvdh/laravel-dompdf) - PDF Generation
- [Pest](https://pestphp.com) - Testing Framework

---

**Version:** 1.0
**Status:** Production Ready
**Last Updated:** 2025-11-09
