# ReferHarmony MVP Plan

## Project Overview

ReferHarmony is a patient-facing healthcare management platform designed to help patients navigate their healthcare journey with clarity and ease. The MVP focuses on providing patients with tools to manage appointments, track tasks, and discover relevant community health events.

## Core MVP Objectives

The MVP should:

1. **Provide a patient-facing web interface** that's fully responsive (mobile-first)
2. **Allow basic CRUD** for appointments and tasks
3. **Display a personalized event feed** filtered by patient health interests
4. **Use SQLite** for local development and easy deployment
5. **Use Laravel Fortify + Livewire** for authentication and interactive components
6. **Deploy to Laravel Cloud** for production hosting

---

## System Architecture

- **Framework**: Laravel 12 (latest version)
- **Frontend**: Livewire Volt + Flux UI + TailwindCSS v4
- **Database**: SQLite (single .sqlite file)
- **Authentication**: Laravel Fortify (email/password login, registration)
- **Storage**: Local disk for appointment documents (`/storage/app/public/appointment_docs`)
- **Deployment**: Laravel Cloud
- **Controllers**: CRUD controllers for Appointments, Tasks, Events
- **Policies**: User-based ownership enforcement

---

## Database Schema

### users
(Laravel default table)
- `id`
- `name`
- `email`
- `password`
- `remember_token`
- `created_at` / `updated_at`

### patients
- `id`
- `user_id` (FK → users)
- `summary` (text, nullable) - Health summary/notes
- `created_at` / `updated_at`

### patient_appointments
- `id`
- `patient_id` (FK → patients)
- `date` (date)
- `time` (time, nullable)
- `partner` (string, nullable) - Healthcare provider/partner name
- `location` (string, nullable)
- `summary` (text, nullable)
- `patient_notes` (text, nullable)
- `created_at` / `updated_at`

### patient_appointment_documents
- `id`
- `patient_appointment_id` (FK → patient_appointments)
- `file_path` (string)
- `summary` (text, nullable) - Description of document
- `created_at` / `updated_at`

### patient_tasks
- `id`
- `patient_id` (FK → patients)
- `description` (string)
- `instructions` (text, nullable)
- `completed_at` (timestamp, nullable)
- `created_at` / `updated_at`

### community_partners
- `id`
- `name` (string)
- `is_nonprofit` (boolean)
- `is_sponsor` (boolean)
- `created_at` / `updated_at`

### community_events
- `id`
- `community_partner_id` (FK → community_partners)
- `date` (date)
- `time` (time, nullable)
- `location` (string, nullable)
- `description` (text)
- `is_partner_provided` (boolean)
- `created_at` / `updated_at`

### healthcare_systems
- `id`
- `name` (string)
- `is_preferred` (boolean)
- `created_at` / `updated_at`

### healthcare_providers
- `id`
- `healthcare_system_id` (FK → healthcare_systems)
- `name` (string)
- `location` (string)
- `phone` (string, nullable)
- `email` (string, nullable)
- `created_at` / `updated_at`

### healthcare_provider_availability
- `id`
- `healthcare_provider_id` (FK → healthcare_providers)
- `date` (date)
- `time` (time)
- `created_at` / `updated_at`

---

## Public Homepage (Unauthenticated)

### ReferHarmony: Bridging Care with Clarity and Confidence

**Your Healthcare Journey, Simplified**

Managing your health appointments, tasks, and resources shouldn't be overwhelming. ReferHarmony helps you stay organized and informed every step of the way.

**Features for You:**
- Track all your healthcare appointments in one place
- Never miss important health tasks and follow-ups
- Discover community health events and resources near you
- Securely store appointment documents and notes

**Get Started Today** [Sign Up Button → /register]

**Already have an account?** [Sign In → /login]

---

## Authenticated User Views and Flows

### 1. Dashboard (Home Page)

**Main Sections:**

**Upcoming Appointments** (Top 3)
- Display as clickable cards
- Show: Date, Time, Provider/Partner, Location
- "View All Appointments" button

**Pending Tasks**
- Clickable list of incomplete tasks
- Checkbox to mark complete
- "View All Tasks" button

**Divider**

**Community Events Feed**
- Filtered by relevance to patient's health interests
- Show: Date, Partner, Event Title, Location
- Click to view event details

**Filtering Logic for Event Feed:**
- Match community event keywords/categories with appointment summaries or task keywords
- Simple fuzzy matching (e.g., "diabetes" in appointment → show diabetes workshops)
- Can be enhanced post-MVP with tagging system

---

### 2. Appointment Manager

**List View:**
- All appointments (past and future)
- Sortable by date
- "Add New Appointment" button

**Add Appointment Form:**
- Date picker (required)
- Time (optional)
- **If past appointment:**
  - Provider (required)
  - Patient notes (required)
  - Document upload (optional)
- **If future appointment:**
  - Provider (optional)
  - Location (optional)
  - Summary/reason (optional)

**Appointment Details Page:**
- Display all fields: date, time, provider, location, summary, notes
- Show attached documents (filename + summary)
  - Click to download/view
- Edit notes in-place (using Livewire for interactivity)
- "Edit Appointment" button
- "Delete Appointment" button (with confirmation)

---

### 3. Task Manager

**List View:**
- All tasks (pending and completed)
- Filter: Show completed / Show pending
- "Add New Task" button

**Add Task Form:**
- Description (required)
- Instructions (optional)

**Task Details:**
- Display description and instructions
- "Mark as Complete" button (if not completed)
- "Mark as Incomplete" button (if completed)
- "Edit Task" button
- "Delete Task" button (with confirmation)

---

### 4. Event Details Page

**Display:**
- Event date and time
- Community partner name
- Location
- Full description
- Optional: Link to partner page (future enhancement)

---

## Development Milestones

### Milestone 1: Foundation Setup
**Goal:** Get Laravel 12 project scaffolded with authentication and basic structure

**Tasks:**
1. Create fresh Laravel 12 project
2. Configure SQLite database connection
3. Install and configure Laravel Fortify for authentication
4. Install Livewire and Flux UI
5. Configure TailwindCSS v4
6. Set up basic layouts using Flux components
7. Create welcome/public homepage view
8. Test registration and login flows
9. Set up version control and initial commit

**Deliverables:**
- Working Laravel 12 installation
- Functional authentication (register, login, logout)
- Public homepage with patient-focused messaging
- Basic responsive layout

**Testing Checklist:**
- [ ] User can register a new account
- [ ] User can log in with email/password
- [ ] User can log out
- [ ] Public homepage displays correctly on mobile and desktop
- [ ] Fortify routes are working (`list-routes` with vendor filter)

---

### Milestone 2: Database Schema & Models
**Goal:** Create all database tables and Eloquent models with relationships

**Tasks:**
1. Create migration for `patients` table
2. Create migration for `patient_appointments` table
3. Create migration for `patient_appointment_documents` table
4. Create migration for `patient_tasks` table
5. Create migration for `community_partners` table
6. Create migration for `community_events` table
7. Create migration for `healthcare_systems` table
8. Create migration for `healthcare_providers` table
9. Create migration for `healthcare_provider_availability` table
10. Create Eloquent models for all tables with proper relationships
11. Create model factories for all models
12. Create database seeders with sample data
13. Run migrations and seeders

**Deliverables:**
- All database tables created
- All Eloquent models with relationships defined
- Factories for testing
- Seeded database with sample data

**Testing Checklist:**
- [ ] All migrations run successfully
- [ ] Seeders populate database with sample data
- [ ] Model relationships work correctly (test with Tinker)
- [ ] Factories can create test data
- [ ] `database-schema` tool shows correct structure

---

### Milestone 3: Patient Record Creation
**Goal:** Automatically create patient record for new users

**Tasks:**
1. Create an event listener or observer for User creation
2. Automatically create a `Patient` record when a user registers
3. Update registration flow to link user to patient
4. Create test to verify patient creation on registration

**Deliverables:**
- Auto-creation of patient record on user registration
- Working user-to-patient relationship

**Testing Checklist:**
- [ ] New user registration creates patient record
- [ ] User can access their patient record after registration
- [ ] Test verifies patient creation

---

### Milestone 4: Dashboard (Home Page)
**Goal:** Build the authenticated user dashboard with appointments, tasks, and events

**Tasks:**
1. Create Dashboard controller/Volt component
2. Build dashboard view using Flux UI components
3. Display upcoming appointments (top 3)
4. Display pending tasks
5. Implement event feed with basic filtering logic
6. Add "View All" navigation buttons
7. Make appointment cards and task items clickable
8. Create responsive layout for mobile and desktop
9. Write feature test for dashboard

**Deliverables:**
- Functional dashboard showing appointments, tasks, and events
- Responsive design
- Basic event filtering

**Testing Checklist:**
- [ ] Dashboard loads for authenticated user
- [ ] Upcoming appointments display correctly (max 3)
- [ ] Pending tasks display correctly
- [ ] Event feed shows relevant events
- [ ] "View All" buttons navigate correctly
- [ ] Mobile layout works properly
- [ ] Feature test passes

---

### Milestone 5: Appointment Manager - List & Create
**Goal:** Allow users to view all appointments and create new ones

**Tasks:**
1. Create Appointment index page (list all appointments)
2. Create "Add Appointment" form with Livewire Volt
3. Implement date/time pickers
4. Add conditional logic for past vs future appointments
5. Implement file upload for appointment documents
6. Create appointment validation (Form Request)
7. Store appointment in database
8. Create feature tests for appointment creation
9. Add authorization policy for appointments

**Deliverables:**
- Appointment list page
- Working appointment creation form
- File upload functionality
- Form validation

**Testing Checklist:**
- [ ] User can view all their appointments
- [ ] User can create new appointment with required fields
- [ ] Past appointments require provider and notes
- [ ] File upload works correctly
- [ ] Validation errors display properly
- [ ] User can only see their own appointments
- [ ] Feature tests pass

---

### Milestone 6: Appointment Manager - View, Edit, Delete
**Goal:** Complete CRUD operations for appointments

**Tasks:**
1. Create appointment details page
2. Display all appointment information
3. Show attached documents with download links
4. Add edit appointment form (pre-filled with existing data)
5. Implement delete appointment with confirmation modal
6. Allow in-place editing of patient notes using Livewire
7. Update authorization policies
8. Create feature tests for edit and delete

**Deliverables:**
- Appointment details view
- Edit appointment functionality
- Delete appointment with confirmation
- In-place notes editing

**Testing Checklist:**
- [ ] User can view appointment details
- [ ] User can edit existing appointment
- [ ] User can delete appointment (with confirmation)
- [ ] Patient notes can be edited in-place
- [ ] Documents display and can be downloaded
- [ ] Authorization prevents accessing other users' appointments
- [ ] Feature tests pass

---

### Milestone 7: Task Manager
**Goal:** Implement complete CRUD for patient tasks

**Tasks:**
1. Create Task index page (list all tasks)
2. Add filter to show completed/pending tasks
3. Create "Add Task" form with Livewire Volt
4. Implement task validation (Form Request)
5. Create task details page
6. Add "Mark as Complete/Incomplete" functionality
7. Implement edit task form
8. Implement delete task with confirmation
9. Create authorization policy for tasks
10. Create feature tests for all task operations

**Deliverables:**
- Task list page with filtering
- Complete CRUD for tasks
- Mark complete/incomplete functionality
- Form validation

**Testing Checklist:**
- [ ] User can view all their tasks
- [ ] User can filter by pending/completed
- [ ] User can create new task
- [ ] User can mark task as complete/incomplete
- [ ] User can edit existing task
- [ ] User can delete task (with confirmation)
- [ ] User can only see their own tasks
- [ ] Feature tests pass

---

### Milestone 8: Event Details & Feed Enhancement
**Goal:** Implement event details page and improve feed filtering

**Tasks:**
1. Create event details page
2. Display event information (date, time, partner, location, description)
3. Enhance event feed filtering logic
4. Add keyword matching between appointments/tasks and events
5. Create feature test for event display
6. Optimize database queries for event feed (eager loading)

**Deliverables:**
- Event details page
- Improved event feed filtering
- Performance optimization

**Testing Checklist:**
- [ ] User can view event details
- [ ] Event feed shows relevant events based on appointments/tasks
- [ ] No N+1 query problems in event feed
- [ ] Event details display correctly
- [ ] Feature test passes

---

### Milestone 9: UI/UX Polish & Mobile Optimization
**Goal:** Ensure excellent user experience across all devices

**Tasks:**
1. Review all pages for Flux UI component consistency
2. Implement loading states with `wire:loading`
3. Add proper form validation error messages
4. Ensure all forms have proper `wire:dirty` states
5. Test all pages on mobile viewport
6. Add empty states for lists (no appointments, no tasks, no events)
7. Implement success/error flash messages
8. Add breadcrumbs or navigation helpers
9. Ensure dark mode support (if applicable)
10. Browser testing on Chrome, Firefox, Safari

**Deliverables:**
- Polished, consistent UI across all pages
- Excellent mobile experience
- Helpful empty states and loading indicators
- Clear user feedback

**Testing Checklist:**
- [ ] All pages work on mobile (320px width minimum)
- [ ] Loading states display correctly
- [ ] Form validation errors are user-friendly
- [ ] Empty states show helpful messages
- [ ] Flash messages work correctly
- [ ] Navigation is intuitive
- [ ] No console errors in browser
- [ ] Cross-browser compatibility verified

---

### Milestone 10: Testing & Code Quality
**Goal:** Ensure comprehensive test coverage and code quality

**Tasks:**
1. Write/update feature tests for all major user flows
2. Write unit tests for key business logic
3. Ensure test coverage for:
   - Authentication flows
   - Appointment CRUD
   - Task CRUD
   - Event display
   - Authorization/policies
4. Run Laravel Pint to format all code
5. Fix any code style issues
6. Review and refactor any duplicate code
7. Run full test suite and ensure 100% pass rate
8. Test file uploads thoroughly
9. Test edge cases (empty data, invalid inputs, etc.)

**Deliverables:**
- Comprehensive test suite
- Clean, formatted code
- All tests passing

**Testing Checklist:**
- [ ] All feature tests pass
- [ ] All unit tests pass
- [ ] Code formatted with Pint
- [ ] No N+1 query issues
- [ ] File uploads tested
- [ ] Edge cases covered
- [ ] Authorization tests pass

---

### Milestone 11: Deployment Preparation
**Goal:** Prepare application for Laravel Cloud deployment

**Tasks:**
1. Review `.env.example` and ensure all variables are documented
2. Configure Laravel Cloud deployment settings
3. Set up production database (SQLite or upgrade to MySQL/PostgreSQL)
4. Configure file storage for production (consider S3 or Laravel Cloud storage)
5. Set up proper error logging
6. Configure mail service for password resets
7. Run production build (`npm run build`)
8. Test deployment to staging environment
9. Create deployment documentation

**Deliverables:**
- Application ready for Laravel Cloud deployment
- Environment configuration documented
- Production-ready build

**Testing Checklist:**
- [ ] Application deploys successfully to Laravel Cloud
- [ ] Database migrations run in production
- [ ] File uploads work in production
- [ ] Email notifications work
- [ ] Error logging configured
- [ ] HTTPS working correctly
- [ ] All environment variables set

---

### Milestone 12: Final Review & Launch
**Goal:** Final testing and production launch

**Tasks:**
1. Perform end-to-end testing on staging
2. Test all user flows (registration, appointments, tasks, events)
3. Verify mobile experience on real devices
4. Check performance and load times
5. Review security (CSRF, XSS, SQL injection prevention)
6. Create user documentation/help guide (optional)
7. Set up monitoring and alerts
8. Deploy to production on Laravel Cloud
9. Verify production deployment
10. Create post-launch monitoring checklist

**Deliverables:**
- Fully tested application
- Production deployment
- Monitoring in place

**Testing Checklist:**
- [ ] All user flows work in production
- [ ] Mobile experience verified on real devices
- [ ] Performance is acceptable (page loads < 2s)
- [ ] No security vulnerabilities
- [ ] Monitoring and alerts configured
- [ ] Backup strategy in place
- [ ] Production deployment successful

---

## Security & Access Control

### Authentication
- Laravel Fortify handles authentication (registration, login, password reset)
- Email/password only for MVP
- Password reset via email

### Authorization
- Each patient record is tied to one user via `user_id`
- All CRUD actions scoped to authenticated user's `patient_id`
- Laravel Policies enforce ownership:
  - `AppointmentPolicy`: User can only manage their own appointments
  - `TaskPolicy`: User can only manage their own tasks
  - Patient record auto-created on registration

### Data Protection
- CSRF protection on all forms (Laravel default)
- XSS prevention via Blade escaping
- SQL injection prevention via Eloquent/Query Builder
- File upload validation (type, size limits)
- Secure file storage in non-public directory

---

## Optional Post-MVP Enhancements

These features can be added after MVP launch:

### Phase 2 Features
- Search/filter on appointment and task lists
- Calendar view for appointments
- Tagging/category system for community events (smarter feed filtering)
- Notification system (email/SMS reminders)
- Export appointments to ICS/Calendar
- Print appointment summary

### Phase 3 Features
- REST API for mobile app development
- Two-factor authentication (Fortify feature)
- Profile management (update email, password)
- Advanced event recommendations using ML/AI
- Integration with healthcare provider systems
- Appointment sharing with caregivers
- Multi-language support

### Nice-to-Have UI Improvements
- Drag-and-drop file uploads
- Inline calendar date picker
- Toast notifications instead of flash messages
- Dark mode toggle
- Customizable dashboard widgets
- PDF generation for appointment summaries

---

## Success Metrics

### MVP Success Criteria
1. **Functional**: All core features work without critical bugs
2. **Performant**: Page loads < 2 seconds
3. **Accessible**: Mobile-responsive, works on iOS Safari and Android Chrome
4. **Secure**: No security vulnerabilities in production
5. **Tested**: 80%+ code coverage with passing tests
6. **Deployed**: Successfully running on Laravel Cloud

### User Success Metrics (Post-Launch)
- User registration rate
- Daily/weekly active users
- Average appointments per user
- Task completion rate
- Event engagement (views, clicks)
- User retention (7-day, 30-day)

---

## Development Best Practices

### Code Standards
- Follow Laravel conventions and best practices
- Use Livewire Volt for interactive components
- Use Flux UI components consistently
- Run Pint before committing code
- Write descriptive commit messages

### Testing Standards
- Write feature tests for all user-facing functionality
- Write unit tests for business logic
- Use factories for test data
- Run tests before pushing code
- Maintain 80%+ code coverage

### Git Workflow
- Create feature branches for each milestone
- Use descriptive branch names (`feature/appointment-crud`, `feature/task-manager`)
- Commit frequently with clear messages
- Review and test before merging to main
- Tag releases (`v1.0.0-mvp`)

---

## Support & Resources

### Laravel 12 Documentation
- Use `search-docs` tool for version-specific documentation
- Laravel official docs: https://laravel.com/docs/12.x
- Livewire docs: https://livewire.laravel.com
- Flux UI docs: https://fluxui.dev

### Laravel Cloud
- Deployment guide: https://cloud.laravel.com/docs
- Support: Laravel Cloud dashboard

### Development Tools
- `php artisan tinker` - for debugging
- `list-routes` - to see all routes
- `database-schema` - to inspect database structure
- `browser-logs` - for frontend debugging
- `last-error` - for backend error details

---

## Notes

- This plan is flexible and can be adjusted based on feedback after each milestone
- Each milestone should be completed, tested, and reviewed before proceeding
- Focus on core functionality first; enhancements can wait
- Prioritize user experience and security throughout development
- Document any deviations from this plan for future reference

---

**Last Updated:** 2025-11-08
**Version:** 1.0
**Status:** Ready for Development
