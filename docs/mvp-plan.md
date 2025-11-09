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
- `latitude` (decimal, nullable) - Patient location for distance calculations
- `longitude` (decimal, nullable) - Patient location for distance calculations
- `plain_english_record` (text, nullable) - AI-generated cumulative health history from all past appointments
- `executive_summary` (text, nullable) - Most recent AI-generated health status summary
- `executive_summary_updated_at` (timestamp, nullable) - When executive summary was last generated
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
- `scheduled_from_task_id` (FK → patient_tasks, nullable) - Links appointment to scheduling task
- `created_at` / `updated_at`

**Note:** Past appointments (date < today) function as healthcare encounters and contain visit summaries

### patient_appointment_documents
- `id`
- `patient_appointment_id` (FK → patient_appointments)
- `file_path` (string)
- `summary` (text, nullable) - Description of document
- `created_at` / `updated_at`

### patient_tasks
- `id`
- `patient_id` (FK → patients)
- `patient_appointment_id` (FK → patient_appointments, nullable) - Tasks may optionally belong to an appointment
- `description` (string)
- `instructions` (text, nullable)
- `is_scheduling_task` (boolean, default false) - Identifies tasks like "Schedule MRI" that trigger scheduling workflow
- `provider_specialty_needed` (string, nullable) - Specialty required for scheduling tasks (e.g., "Cardiology", "Radiology")
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
- `specialty` (string, nullable) - e.g., "Cardiology", "Radiology", "Family Medicine"
- `location` (string)
- `latitude` (decimal, nullable) - Provider location for distance calculations
- `longitude` (decimal, nullable) - Provider location for distance calculations
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
- Show appointment link if task is related to an appointment
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
- **Related Tasks Section:**
  - Display list of tasks related to this appointment
  - Show task description and completion status (checkbox)
  - Allow marking tasks as complete/incomplete inline
  - "Add Task" button to create a new task linked to this appointment
- "Edit Appointment" button
- "Delete Appointment" button (with confirmation)

---

### 3. Task Manager

**List View:**
- All tasks (pending and completed)
- Show if task is linked to an appointment (with link to appointment details)
- Filter: Show completed / Show pending
- "Add New Task" button

**Add Task Form:**
- Description (required)
- Instructions (optional)
- Optional: Link to appointment (dropdown of appointments)

**Task Details:**
- Display description and instructions
- Show linked appointment (if any) with link to appointment details
- "Mark as Complete" button (if not completed)
- "Mark as Incomplete" button (if completed)
- "Edit Task" button
- "Delete Task" button (with confirmation)

**Note:** Tasks can be created from either the Task Manager OR directly from an Appointment Details page

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
4. Create migration for `patient_tasks` table (include nullable `patient_appointment_id` foreign key)
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
**Goal:** Build the authenticated user dashboard with appointments, tasks, executive summary, and events

**Tasks:**
1. Create Dashboard controller/Volt component
2. Build dashboard view using Flux UI components
3. Display upcoming appointments (top 3) with calculated distance from patient location
4. Display pending tasks with "Schedule" button for scheduling tasks
5. Create Executive Summary card (positioned between tasks and divider)
   - Display most recent `executive_summary` from patient record
   - Show `executive_summary_updated_at` date
   - Make card clickable → links to `/timeline` page
   - Style prominently to stand out
6. Implement event feed with basic filtering logic
7. Add "View All" navigation buttons
8. Make appointment cards and task items clickable
9. Create helper function for distance calculation (Haversine formula)
10. Create responsive layout for mobile and desktop
11. Write feature test for dashboard

**Deliverables:**
- Functional dashboard showing appointments, tasks, executive summary, and events
- Provider distance calculation from patient coordinates
- Executive Summary card with link to timeline
- Scheduling task identification and "Schedule" buttons
- Responsive design
- Basic event filtering

**Testing Checklist:**
- [ ] Dashboard loads for authenticated user
- [ ] Upcoming appointments display correctly (max 3) with distance
- [ ] Pending tasks display correctly
- [ ] Scheduling tasks show "Schedule" button
- [ ] Executive Summary card displays between tasks and feed
- [ ] Executive Summary card is clickable and links to `/timeline`
- [ ] Distance calculation works correctly
- [ ] Event feed shows relevant events
- [ ] "View All" buttons navigate correctly
- [ ] Mobile layout works properly
- [ ] Feature test passes

---

### Milestone 5: Mock Healthcare Data Command
**Goal:** Create command to generate realistic healthcare encounters with AI-powered summaries

**Tasks:**
1. Install and configure DomPDF for PDF generation
2. Set up OpenAI API integration
   - Add `OPENAI_API_KEY` to `.env` and `.env.example`
   - Create service class for AI summary generation
3. Create Artisan command: `php artisan mock:healthcare-encounter {patient_id}`
4. Implement command logic for generating new family physician visits:
   - Create past appointment (e.g., 2 weeks ago)
   - Add visit summary and patient notes
   - Generate realistic PDF visit summary document using DomPDF
   - Create referral tasks (e.g., "Schedule Cardiology appointment")
   - Optionally create future specialist appointments
   - Create follow-up appointment with family physician
5. Implement command logic for updating existing appointments:
   - Convert future appointment to past appointment
   - Add visit summary and documents
   - Generate new referral tasks if applicable
6. Implement AI summary generation:
   - Call OpenAI API to generate Plain English Patient Record from all past appointments
   - Call OpenAI API to generate Executive Summary
   - Update patient record with both summaries
   - Only regenerate when new past appointment is added
7. Create PDF templates with official-looking format:
   - Provider letterhead
   - Patient demographics
   - Visit date and chief complaint
   - Assessment and plan
   - Referrals section
8. Seed database with Joplin, MO area coordinates:
   - Center point: 37.0842° N, 94.5133° W
   - Generate random coordinates for providers (~20 mile radius)
   - Generate random coordinates for patients (~10 mile radius)
9. Create feature tests for command
10. Test various scenarios (new visit, update existing, with/without referrals)

**Deliverables:**
- Working Artisan command for generating mock encounters
- DomPDF integration with professional-looking templates
- OpenAI API integration for AI summaries
- Seeded coordinates for Joplin area
- Realistic sample data generation

**Testing Checklist:**
- [ ] Command runs successfully for given patient
- [ ] Past appointments are created with summaries
- [ ] PDF documents are generated and stored correctly
- [ ] PDF documents look professional and realistic
- [ ] Referral tasks are created appropriately
- [ ] Plain English Patient Record is generated by AI
- [ ] Executive Summary is generated by AI
- [ ] Summaries only regenerate when new past appointment added
- [ ] Patient coordinates are in Joplin area
- [ ] Provider coordinates are in Joplin area
- [ ] Feature tests pass for various scenarios

---

### Milestone 6: Appointment Manager - List & Create
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

### Milestone 7: Mock Scheduling Workflow
**Goal:** Implement task-based appointment scheduling with provider selection and availability

**Tasks:**
1. Update Task model and forms to support scheduling tasks:
   - Add `is_scheduling_task` field
   - Add `provider_specialty_needed` field
   - Update task creation/edit forms
2. Add "Schedule" button to scheduling tasks on dashboard and task detail pages
3. Create provider selection page/component for scheduling workflow:
   - Filter providers by specialty from task
   - Display provider information (name, location, phone, system)
   - Calculate and display distance from patient location
   - Highlight preferred healthcare system providers
4. Implement distance calculation helper:
   - Use Haversine formula for lat/long distance
   - Display as "X.X miles away"
5. Generate mock availability for preferred system providers:
   - Create 3-5 fake time slots
   - Next 2 weeks, weekdays only (8 AM - 4 PM)
   - Display as clickable buttons (e.g., "Nov 14 10:00 AM")
6. Create appointment creation flow from availability:
   - Click time slot → redirect to appointment form
   - Pre-fill provider, date, time
   - Allow user to review and save
   - Link appointment to scheduling task via `scheduled_from_task_id`
   - Mark scheduling task as complete
7. Add "Independent Providers" section showing non-system providers
   - Note that these need manual scheduling outside the app
8. Create feature tests for scheduling workflow
9. Add authorization checks

**Deliverables:**
- Scheduling task identification and UI
- Provider selection interface with distance
- Mock availability generation
- Appointment creation from scheduling
- Distance calculation helper
- Complete scheduling workflow

**Testing Checklist:**
- [ ] Scheduling tasks show "Schedule" button
- [ ] Clicking "Schedule" shows filtered provider list
- [ ] Providers are filtered by specialty correctly
- [ ] Distance is calculated and displayed accurately
- [ ] Preferred system providers show availability
- [ ] Non-preferred providers don't show availability
- [ ] Clicking availability slot creates appointment with correct data
- [ ] Appointment is linked to scheduling task
- [ ] Scheduling task is marked complete after appointment creation
- [ ] Distance calculation works for various coordinates
- [ ] Feature tests pass for complete workflow

---

### Milestone 8: Appointment Manager - View, Edit, Delete
**Goal:** Complete CRUD operations for appointments and add task management to appointments

**Tasks:**
1. Create appointment details page
2. Display all appointment information
3. Show attached documents with download links
4. Display related tasks (linked to this appointment)
5. Allow marking tasks as complete/incomplete inline on appointment page
6. Add "Add Task" functionality to create tasks linked to the appointment
7. Add edit appointment form (pre-filled with existing data)
8. Implement delete appointment with confirmation modal
9. Allow in-place editing of patient notes using Livewire
10. Update authorization policies
11. Create feature tests for edit, delete, and task management

**Deliverables:**
- Appointment details view with related tasks
- Task management on appointment page (view, add, complete/incomplete)
- Edit appointment functionality
- Delete appointment with confirmation
- In-place notes editing

**Testing Checklist:**
- [ ] User can view appointment details
- [ ] Related tasks display correctly on appointment page
- [ ] User can add new task linked to appointment
- [ ] User can mark tasks as complete/incomplete from appointment page
- [ ] User can edit existing appointment
- [ ] User can delete appointment (with confirmation)
- [ ] Patient notes can be edited in-place
- [ ] Documents display and can be downloaded
- [ ] Authorization prevents accessing other users' appointments
- [ ] Feature tests pass for all appointment and task operations

---

### Milestone 9: Task Manager
**Goal:** Implement complete CRUD for patient tasks with optional appointment linking

**Tasks:**
1. Create Task index page (list all tasks)
2. Add filter to show completed/pending tasks
3. Show appointment link on task list (if task is linked to appointment)
4. Create "Add Task" form with Livewire Volt
5. Add optional appointment dropdown to task form
6. Implement task validation (Form Request)
7. Create task details page
8. Display linked appointment on task details (if applicable)
9. Add "Mark as Complete/Incomplete" functionality
10. Implement edit task form (including ability to link/unlink appointment)
11. Implement delete task with confirmation
12. Create authorization policy for tasks
13. Create feature tests for all task operations (including appointment linking)

**Deliverables:**
- Task list page with filtering and appointment links
- Complete CRUD for tasks with appointment linking
- Mark complete/incomplete functionality
- Form validation

**Testing Checklist:**
- [ ] User can view all their tasks
- [ ] Tasks show linked appointment (if applicable)
- [ ] User can filter by pending/completed
- [ ] User can create new task with optional appointment link
- [ ] User can create task from appointment page (tested in Milestone 8)
- [ ] User can mark task as complete/incomplete
- [ ] User can edit existing task and change appointment link
- [ ] User can delete task (with confirmation)
- [ ] User can only see their own tasks
- [ ] Feature tests pass for all scenarios

---

### Milestone 10: Encounter Timeline Page
**Goal:** Create timeline view of patient's healthcare encounters with Plain English Patient Record

**Tasks:**
1. Create route `/timeline` for encounter timeline page
2. Create Timeline controller/Volt component
3. Build timeline page layout:
   - Header section with Plain English Patient Record
   - Show when record was last updated
   - Prominent heading: "Your Health Story in Plain English"
4. Display all past appointments in reverse chronological order:
   - Query appointments where `date` < today
   - Show date, provider name, specialty (if available)
   - Display visit summary
   - Show attached documents with download links
   - Display related tasks created from each visit
5. Implement visual timeline design:
   - Use timeline component from Flux UI or create custom
   - Date markers for each encounter
   - Clear visual hierarchy
6. Add actions:
   - "Download Full Health Record" button (optional - PDF export)
   - "Back to Dashboard" link
7. Ensure responsive design for mobile
8. Create feature tests for timeline page
9. Add authorization (user can only view their own timeline)

**Deliverables:**
- Working `/timeline` route and page
- Display of Plain English Patient Record
- Chronological list of past appointments (encounters)
- Document links and related tasks
- Responsive timeline design

**Testing Checklist:**
- [ ] Timeline page loads for authenticated user at `/timeline`
- [ ] Plain English Patient Record displays at top
- [ ] Last updated timestamp shows correctly
- [ ] Past appointments display in reverse chronological order
- [ ] Future appointments are NOT shown on timeline
- [ ] Visit summaries display correctly
- [ ] Attached documents are shown with download links
- [ ] Related tasks display for each encounter
- [ ] Timeline works on mobile devices
- [ ] User can only access their own timeline
- [ ] Feature tests pass

---

### Milestone 11: Event Details & Feed Enhancement
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

### Milestone 12: UI/UX Polish & Mobile Optimization
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

### Milestone 13: Testing & Code Quality
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

### Milestone 14: Deployment Preparation
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

### Milestone 15: Final Review & Launch
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
- **Real Healthcare System Integration**: Replace mock data with actual EHR/EMR API integration
- **Real Appointment Scheduling**: Integration with provider scheduling systems for live availability
- **Summary of Services**: Auto-generate and send pre-visit summaries to providers 3 days before appointments
- **EOB (Explanation of Benefits) Management**: Upload, view, and analyze EOBs with financial breakdowns
- **Search/filter on appointment and task lists**: Advanced filtering and search capabilities
- **Calendar view for appointments**: Interactive calendar interface
- **Notification system**: Email/SMS reminders for appointments and tasks
- **Patient location updates**: Allow patients to update their location for accurate distance calculations
- **Export appointments to ICS/Calendar**: Integration with personal calendars

### Phase 3 Features
- **Advanced AI Features**:
  - OCR for uploaded documents
  - Natural language processing for encounter notes
  - Predictive health insights
  - Automated task creation from appointments
- **Geocoding and Mapping**: Real address geocoding with map views for providers
- **REST API for mobile app development**
- **Two-factor authentication** (Fortify feature)
- **Profile management** (update email, password, preferences)
- **Tagging/category system for community events** (smarter feed filtering)
- **Advanced event recommendations using ML/AI**
- **Appointment sharing with caregivers**
- **Multi-language support**
- **Medication tracking and reminders**
- **Lab results integration**

### Nice-to-Have UI Improvements
- **Drag-and-drop file uploads**
- **Inline calendar date picker**
- **Toast notifications instead of flash messages**
- **Dark mode toggle**
- **Customizable dashboard widgets**
- **Interactive provider map view**
- **Print/export encounter timeline**
- **Bulk task operations**

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
**Version:** 1.2
**Status:** Ready for Development

**Changelog:**
- v1.2: **Healthcare Integration Features**
  - Added new database fields to `patients`: `latitude`, `longitude`, `plain_english_record`, `executive_summary`, `executive_summary_updated_at`
  - Added new database fields to `healthcare_providers`: `latitude`, `longitude`, `specialty`
  - Added new database fields to `patient_tasks`: `is_scheduling_task`, `provider_specialty_needed`
  - Added new database field to `patient_appointments`: `scheduled_from_task_id`
  - Added note that past appointments (date < today) function as healthcare encounters
  - **NEW Milestone 5**: Mock Healthcare Data Command - Generate realistic encounters with AI summaries
  - **NEW Milestone 7**: Mock Scheduling Workflow - Task-based appointment scheduling with provider selection
  - **NEW Milestone 10**: Encounter Timeline Page - Display patient health history at `/timeline`
  - Updated Milestone 4 (Dashboard) to include Executive Summary card and distance calculations
  - Renumbered milestones: Original 5→6, 6→8, 7→9, 8→11, 9→12, 10→13, 11→14, 12→15
  - Added OpenAI API integration for AI-generated summaries
  - Added DomPDF integration for mock healthcare documents
  - Added Joplin, MO area coordinates for seeded data
  - Added Haversine formula for distance calculations
  - Updated post-MVP enhancements to reflect new architecture
- v1.1: Added `patient_appointment_id` to tasks table, allowing tasks to optionally belong to appointments
- v1.1: Updated Appointment Details page to display related tasks and allow adding tasks
- v1.1: Enhanced Task Manager to support appointment linking
- v1.0: Initial MVP plan created
