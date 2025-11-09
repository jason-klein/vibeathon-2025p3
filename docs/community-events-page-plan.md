# Community Events Page - Implementation Plan

**Version:** 1.0
**Date:** 2025-11-09
**Status:** Ready for Implementation

---

## Overview

Add a dedicated community events page that allows users to browse, search, and filter all upcoming community events. The page will be accessible via a new link in the sidebar navigation.

---

## Requirements

### User Stories

1. **As a patient**, I want to see all upcoming community events in one place so I can discover health-related community resources
2. **As a patient**, I want to search events by keyword so I can find events relevant to my interests
3. **As a patient**, I want to filter events by date range so I can plan ahead
4. **As a patient**, I want to filter events by distance so I can find events near me
5. **As a patient**, I want to click on an event to view full details

### Features

- **Event Listing**: Display all upcoming community events in a paginated list/grid
- **Keyword Search**: Real-time search across event descriptions, partner names, and locations
- **Date Range Filter**: Filter events by start date and end date
- **Distance Filter**: Filter events within X miles of patient location
- **Event Cards**: Consistent design matching dashboard feed (purple theme)
- **Navigation**: Easy access from sidebar, proper back navigation

---

## Technical Implementation

### Phase 1: Database Schema Enhancement

**Goal:** Add distance filtering capability to community events

#### Tasks

1. **Create migration for community_events table**
   ```bash
   php artisan make:migration add_location_coordinates_to_community_events_table
   ```
   - Add `latitude` (decimal(10, 8), nullable)
   - Add `longitude` (decimal(11, 8), nullable)
   - These will enable distance calculations from patient location

2. **Update CommunityEvent model** (`app/Models/CommunityEvent.php`)
   - Add `latitude` and `longitude` to `$fillable` array
   - Ensure proper casting if needed

3. **Update database seeders**
   - Add Joplin, MO area coordinates to seeded events
   - Center point: 37.0842° N, 94.5133° W
   - Generate random coordinates within ~20 mile radius (matching provider coordinates)

**Deliverables:**
- Migration file created and run
- Model updated with new fields
- Seeders updated with realistic coordinates

---

### Phase 2: Create Events Index Page

**Goal:** Build the main community events listing page with filtering

#### Tasks

4. **Create Volt component**
   ```bash
   php artisan make:volt events/index --class
   ```

   Location: `resources/views/livewire/events/index.blade.php`

5. **Implement component logic** (class-based Volt component)

   **State Properties:**
   ```php
   public string $keyword = '';
   public ?string $startDate = null;
   public ?string $endDate = null;
   public ?int $maxDistance = null;
   public int $perPage = 15;
   ```

   **Computed Properties:**
   - `$events` - Filtered and paginated events query
   - `$patient` - Current user's patient record (for distance calculations)

   **Methods:**
   - `clearFilters()` - Reset all filters to defaults
   - `calculateDistance($event)` - Use existing DistanceCalculator helper
   - `formatDistance($distance)` - Format distance display

   **Query Logic:**
   ```php
   $query = CommunityEvent::with('partner')
       ->where('date', '>=', today())
       ->orderBy('date')
       ->orderBy('time');

   // Apply keyword filter
   if ($this->keyword) {
       $query->where(function($q) {
           $q->whereHas('partner', fn($q) =>
               $q->where('name', 'like', "%{$this->keyword}%"))
             ->orWhere('description', 'like', "%{$this->keyword}%")
             ->orWhere('location', 'like', "%{$this->keyword}%");
       });
   }

   // Apply date range filters
   if ($this->startDate) {
       $query->where('date', '>=', $this->startDate);
   }
   if ($this->endDate) {
       $query->where('date', '<=', $this->endDate);
   }

   // Distance filtering happens after query (using Haversine formula)
   $events = $query->get();

   if ($this->maxDistance && $patient?->latitude && $patient?->longitude) {
       $events = $events->filter(function($event) use ($patient) {
           if (!$event->latitude || !$event->longitude) {
               return true; // Include events without coordinates
           }
           $distance = DistanceCalculator::calculate(
               $patient->latitude,
               $patient->longitude,
               $event->latitude,
               $event->longitude
           );
           return $distance <= $this->maxDistance;
       });
   }

   return $events->paginate($this->perPage);
   ```

6. **Design page layout** (using Flux UI components)

   **Structure:**
   ```blade
   <div class="flex h-full w-full flex-1 flex-col gap-6">
       {{-- Page Header --}}
       <div>
           <h1>Community Events</h1>
           <p>Browse upcoming health and wellness events in your area</p>
       </div>

       {{-- Filter Section --}}
       <div class="rounded-xl border bg-white p-6">
           {{-- Keyword Search --}}
           <flux:input
               wire:model.live.debounce.300ms="keyword"
               placeholder="Search events..."
           />

           {{-- Date Filters --}}
           <div class="grid gap-4 md:grid-cols-2">
               <flux:input
                   type="date"
                   wire:model.live="startDate"
                   label="Start Date"
               />
               <flux:input
                   type="date"
                   wire:model.live="endDate"
                   label="End Date"
               />
           </div>

           {{-- Distance Filter --}}
           <flux:select
               wire:model.live="maxDistance"
               label="Distance"
           >
               <option value="">Any distance</option>
               <option value="5">Within 5 miles</option>
               <option value="10">Within 10 miles</option>
               <option value="25">Within 25 miles</option>
               <option value="50">Within 50 miles</option>
               <option value="100">Within 100 miles</option>
           </flux:select>

           {{-- Clear Filters Button --}}
           <flux:button wire:click="clearFilters" variant="ghost">
               Clear Filters
           </flux:button>
       </div>

       {{-- Event Cards Grid --}}
       <div class="space-y-4">
           @forelse($this->events as $event)
               {{-- Event Card (matching dashboard feed style) --}}
               <a href="{{ route('events.show', $event->id) }}"
                  class="block rounded-lg border-2 border-purple-200 bg-purple-50 p-4...">
                   {{-- Event card content --}}
               </a>
           @empty
               {{-- Empty State --}}
           @endforelse
       </div>

       {{-- Pagination --}}
       {{ $this->events->links() }}
   </div>
   ```

   **Event Card Design:**
   - Purple theme (consistent with dashboard feed)
   - Partner icon (group icon)
   - Partner name (title)
   - Event description (truncated to ~150 chars)
   - Date, time, location icons and text
   - Distance badge (if available)
   - Hover effects and transitions

   **Empty State:**
   - Calendar icon
   - "No events found" message
   - Helpful text based on filters active

7. **Add loading states**
   ```blade
   <div wire:loading wire:target="keyword,startDate,endDate,maxDistance">
       <div class="absolute inset-0 bg-white/50 flex items-center justify-center">
           <svg class="animate-spin h-8 w-8 text-purple-600">...</svg>
       </div>
   </div>
   ```

**Deliverables:**
- Functional events index page with filtering
- Real-time keyword search
- Date range filtering
- Distance filtering with patient coordinates
- Pagination
- Loading states
- Empty states
- Mobile responsive design

---

### Phase 3: Navigation & Routing

**Goal:** Integrate events page into application navigation

#### Tasks

8. **Add route** in `routes/web.php`
   ```php
   Volt::route('events', 'events.index')
       ->middleware(['auth', 'verified'])
       ->name('events.index');
   ```

9. **Update sidebar navigation** (`resources/views/components/layouts/app/sidebar.blade.php`)

   Add new item in the "My Health" navlist group (around line 15-20):
   ```blade
   <flux:navlist.item
       icon="calendar-days"
       :href="route('events.index')"
       :current="request()->routeIs('events.*')"
       wire:navigate>
       {{ __('Community Events') }}
   </flux:navlist.item>
   ```

   **Placement:** After "Timeline" item, before `<flux:spacer />`

10. **Update event details back navigation** (`resources/views/livewire/events/show.blade.php`)

    Change line 16 from:
    ```blade
    <a href="{{ route('dashboard') }}">Back to Dashboard</a>
    ```

    To:
    ```blade
    <a href="{{ url()->previous() === route('events.index') ? route('events.index') : route('dashboard') }}">
        Back to {{ url()->previous() === route('events.index') ? 'Events' : 'Dashboard' }}
    </a>
    ```

    Or simpler approach:
    ```blade
    <a href="{{ route('events.index') }}">Back to Events</a>
    ```

    Also update footer button(s) on lines 123-133

**Deliverables:**
- Route registered and accessible
- Sidebar navigation updated
- Event details page back navigation improved
- All navigation uses wire:navigate for SPA-like experience

---

### Phase 4: Distance Calculation Integration

**Goal:** Reuse existing distance calculation infrastructure

#### Tasks

11. **Integrate DistanceCalculator helper** (`App\Support\Helpers\DistanceCalculator`)

    The helper already exists in the codebase (used for appointments). Reuse it for events:
    ```php
    use App\Support\Helpers\DistanceCalculator;

    $distance = DistanceCalculator::calculate(
        $patient->latitude,
        $patient->longitude,
        $event->latitude,
        $event->longitude
    );

    $formattedDistance = DistanceCalculator::format($distance);
    ```

12. **Handle edge cases**
    - Patient has no coordinates: Show all events, hide distance badges
    - Event has no coordinates: Include in results, show no distance
    - Invalid coordinates: Handle gracefully with null checks

13. **Display distance on event cards**
    ```blade
    @if($distance = $this->calculateDistance($event))
        <span class="flex items-center gap-1">
            <svg>...</svg>
            {{ $this->formatDistance($distance) }}
        </span>
    @endif
    ```

**Deliverables:**
- Distance calculation working for events with coordinates
- Distance displayed on event cards
- Graceful handling of missing coordinates
- Distance filter working correctly

---

### Phase 5: Testing

**Goal:** Ensure comprehensive test coverage for new functionality

#### Tasks

14. **Create feature test** (`tests/Feature/Events/EventsIndexTest.php`)
    ```bash
    php artisan make:test Events/EventsIndexTest --pest
    ```

    **Test Cases:**
    ```php
    // Authentication & Authorization
    test('events index requires authentication')
    test('authenticated user can view events index')

    // Basic Display
    test('events index displays upcoming events')
    test('events index does not display past events')
    test('events index shows event count')
    test('events index shows empty state when no events')

    // Keyword Search
    test('keyword filter searches event descriptions')
    test('keyword filter searches partner names')
    test('keyword filter searches locations')
    test('keyword filter is case insensitive')
    test('keyword filter updates results in real-time')

    // Date Range Filtering
    test('start date filter limits events correctly')
    test('end date filter limits events correctly')
    test('date range filter works together')

    // Distance Filtering
    test('distance filter limits events within radius')
    test('distance filter handles missing patient coordinates')
    test('distance filter includes events without coordinates')

    // Clear Filters
    test('clear filters button resets all filters')

    // Pagination
    test('events are paginated correctly')
    test('pagination persists filters')

    // Navigation
    test('clicking event card navigates to event details')
    test('sidebar link is active on events index')

    // Loading States
    test('loading indicator shows during filter updates')
    ```

15. **Update existing tests if needed**
    - Check if any navigation tests need updating
    - Verify route count tests still pass
    - Update any tests checking sidebar items

16. **Run test suite**
    ```bash
    php artisan test --filter=EventsIndex
    php artisan test # Full suite
    ```

**Deliverables:**
- Comprehensive feature test coverage
- All tests passing
- Edge cases covered
- Authorization verified

---

### Phase 6: Code Quality & Polish

**Goal:** Ensure code meets project standards

#### Tasks

17. **Run Laravel Pint**
    ```bash
    vendor/bin/pint resources/views/livewire/events/
    vendor/bin/pint app/Models/CommunityEvent.php
    vendor/bin/pint tests/Feature/Events/
    vendor/bin/pint database/migrations/*community_events*
    ```

18. **Performance optimization**
    - Verify eager loading of `partner` relationship (no N+1 queries)
    - Test pagination performance with many events
    - Consider adding database indexes if needed:
      ```php
      $table->index('date');
      $table->index(['latitude', 'longitude']);
      ```

19. **Accessibility check**
    - Ensure proper ARIA labels on filters
    - Keyboard navigation works
    - Screen reader friendly
    - Proper focus states

20. **Mobile responsiveness**
    - Test on mobile viewport (320px minimum)
    - Ensure filters stack properly
    - Event cards are touch-friendly
    - Pagination works on mobile

21. **Browser testing**
    - Chrome (latest)
    - Firefox (latest)
    - Safari (latest)
    - No console errors
    - No visual glitches

**Deliverables:**
- Code formatted with Pint
- Performance optimized
- Accessibility verified
- Mobile responsive
- Cross-browser compatible

---

## Implementation Checklist

### Phase 1: Database
- [ ] Create migration for latitude/longitude
- [ ] Run migration
- [ ] Update CommunityEvent model fillable
- [ ] Update seeders with coordinates
- [ ] Run seeders to test

### Phase 2: Events Index Page
- [ ] Create Volt component (events/index)
- [ ] Implement state properties (keyword, dates, distance)
- [ ] Implement computed properties (events query)
- [ ] Implement methods (clearFilters, calculateDistance, formatDistance)
- [ ] Build page header
- [ ] Build filter section (keyword, dates, distance)
- [ ] Build event cards grid
- [ ] Build empty state
- [ ] Add pagination
- [ ] Add loading states
- [ ] Test mobile responsiveness

### Phase 3: Navigation
- [ ] Add route in web.php
- [ ] Update sidebar navigation
- [ ] Update event details back link
- [ ] Test all navigation paths

### Phase 4: Distance Calculations
- [ ] Import DistanceCalculator helper
- [ ] Implement distance filtering in query
- [ ] Display distance on event cards
- [ ] Handle missing coordinates gracefully
- [ ] Test with various coordinate scenarios

### Phase 5: Testing
- [ ] Create feature test file
- [ ] Write authentication tests
- [ ] Write display tests
- [ ] Write keyword search tests
- [ ] Write date filter tests
- [ ] Write distance filter tests
- [ ] Write pagination tests
- [ ] Write navigation tests
- [ ] Run all tests and ensure passing
- [ ] Update existing tests if needed

### Phase 6: Polish
- [ ] Run Laravel Pint
- [ ] Check for N+1 queries
- [ ] Verify mobile responsiveness
- [ ] Test accessibility
- [ ] Browser testing (Chrome, Firefox, Safari)
- [ ] Performance testing with many events
- [ ] Final QA review

---

## Estimated Timeline

| Phase | Duration | Total |
|-------|----------|-------|
| Phase 1: Database | 10 minutes | 10 min |
| Phase 2: Events Index | 45 minutes | 55 min |
| Phase 3: Navigation | 10 minutes | 65 min |
| Phase 4: Distance | 20 minutes | 85 min |
| Phase 5: Testing | 30 minutes | 115 min |
| Phase 6: Polish | 20 minutes | 135 min |

**Total Estimated Time:** 2 hours 15 minutes

---

## Design Specifications

### Color Scheme
- **Primary:** Purple (matching community event theme from dashboard)
- **Borders:** `border-purple-200` / `dark:border-purple-800`
- **Background:** `bg-purple-50` / `dark:bg-purple-900/20`
- **Text:** `text-purple-600` / `dark:text-purple-400`

### Icons
- Calendar icon for date
- Clock icon for time
- Location pin icon for location
- Group icon for partner/community events
- Distance/map pin for distance badge

### Typography
- Headings: `text-lg font-semibold text-zinc-900 dark:text-zinc-100`
- Body: `text-sm text-zinc-700 dark:text-zinc-300`
- Metadata: `text-sm text-zinc-600 dark:text-zinc-400`

### Spacing
- Card gap: `gap-4`
- Section gap: `gap-6`
- Card padding: `p-4` or `p-6`

---

## Future Enhancements (Post-MVP)

These features can be added after initial implementation:

### Sort Options
- Date (ascending/descending)
- Distance (nearest first)
- Partner name (alphabetical)

### Additional Filters
- Partner type (nonprofit, sponsor)
- Event type/category (if added to schema)
- "Show Past Events" toggle

### Enhanced Features
- Map view showing event locations
- "Add to Calendar" button (ICS export)
- Share event functionality
- Email notifications for relevant events
- Favorite events
- RSVP functionality

### UI Improvements
- Sticky filter bar on scroll
- Filter count badges
- Advanced search options
- Saved filter presets

---

## Success Metrics

### Functional Requirements
- [ ] Page loads without errors
- [ ] All filters work correctly
- [ ] Distance calculations accurate
- [ ] Pagination works
- [ ] Mobile responsive
- [ ] All tests passing

### User Experience
- [ ] Page loads in < 2 seconds
- [ ] Filters respond in < 300ms
- [ ] No console errors
- [ ] Accessible (WCAG AA)
- [ ] Intuitive navigation

### Code Quality
- [ ] Follows Laravel conventions
- [ ] Uses existing components/helpers
- [ ] Proper error handling
- [ ] No N+1 queries
- [ ] Formatted with Pint
- [ ] 80%+ test coverage

---

## Notes

- This feature builds on existing patterns in the application (appointments, tasks, timeline)
- Reuses existing infrastructure (DistanceCalculator, Flux UI, Volt components)
- Maintains consistency with dashboard feed design
- Follows MVP plan architecture and conventions
- No new dependencies required

---

## References

- MVP Plan: `docs/mvp-plan.md`
- Dashboard Feed: `resources/views/livewire/dashboard.blade.php` (lines 453-573)
- Event Show Page: `resources/views/livewire/events/show.blade.php`
- CommunityEvent Model: `app/Models/CommunityEvent.php`
- DistanceCalculator: `app/Support/Helpers/DistanceCalculator.php`
- Sidebar Navigation: `resources/views/components/layouts/app/sidebar.blade.php`

---

**Last Updated:** 2025-11-09
**Author:** Claude Code
**Status:** Ready for Implementation
