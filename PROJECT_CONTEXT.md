# Spotlight — Project Context for AI Agents

> **Purpose of this document**: Provide an AI coding agent with everything it needs to understand, navigate, and contribute to the Spotlight codebase efficiently.

---

## 1. Project Overview

**Spotlight** is a full-stack **event management and ticketing platform** built as a PFE (Projet de Fin d'Études). It allows organizers to create and manage events (including tournaments), participants to discover and book tickets, affiliates (resellers) to earn commissions through referral links, and administrators to oversee the entire platform — users, events, finances, and reservations.

### Key Capabilities

| Feature Area | Description |
|---|---|
| **Event Management** | Create, edit, publish, cancel events with media (images/videos via Cloudinary) |
| **Tournament Support** | Events can be tournaments (individual or team-based) with separate participant/spectator capacities and pricing |
| **Ticketing & Reservations** | Multi-ticket booking, QR code tickets (PDF download), capacity management |
| **Payments** | Stripe Checkout integration, Stripe Connect for organizer payouts |
| **Affiliate/Reseller System** | Referral codes, commission tracking (80/20 or 80/15/5 split), admin-approved payouts |
| **Real-time Messaging** | WebSocket-based chat between users (Laravel Reverb + Pusher + Laravel Echo) |
| **Notifications** | In-app notification system with multiple types (reservation, collaboration, payment, etc.) |
| **Collaboration** | Organizers can invite co-organizers with granular permissions (can_edit, can_cancel, etc.) |
| **Admin Dashboard** | User management, event approval/rejection, financial management, reservation oversight |
| **AI Integration** | AI-powered event description reformulation and image generation/suggestions |
| **Internationalization** | Full i18n support for French (fr), English (en), and Arabic (ar) via `vue-i18n` |
| **Authentication** | Email/password + Google OAuth + Email verification (OTP code) + 2FA (Laravel Fortify) |
| **Legal Pages** | About Us, Privacy Policy, Terms of Service |

---

## 2. Tech Stack

### Backend
| Technology | Version/Details |
|---|---|
| **PHP** | ^8.2 |
| **Laravel** | ^12.0 |
| **Inertia.js** | ^2.0 (server-side adapter) |
| **Laravel Sanctum** | ^4.3 (API token auth) |
| **Laravel Fortify** | ^1.30 (2FA, password reset) |
| **Laravel Socialite** | ^5.24 (Google OAuth) |
| **Laravel Reverb** | WebSocket server |
| **Laravel Cashier** | ^16.3 (Stripe billing) |
| **Stripe PHP SDK** | ^17.6 |
| **Cloudinary** | ^3.0 (media uploads) |
| **DomPDF** | ^3.1 (ticket PDF generation) |
| **endroid/qr-code** | ^6.1 (QR codes on tickets) |
| **Spatie Translatable** | ^6.14 (translatable category model) |
| **stichoza/google-translate-php** | ^5.3 (auto-translation) |

### Frontend
| Technology | Version/Details |
|---|---|
| **Vue.js** | ^3.5 (Composition API + `<script setup>`) |
| **TypeScript** | ^5.2 |
| **Inertia.js Vue 3** | ^2.3 (client-side adapter) |
| **Tailwind CSS** | ^4.1 (via `@tailwindcss/vite` plugin) |
| **shadcn-vue** | Component library (via `reka-ui` ^2.6) |
| **Pinia** | ^3.0 (state management) |
| **Vue Router** | ^5.0 |
| **vue-i18n** | ^10.0 (internationalization) |
| **Leaflet** | ^1.9 (maps) |
| **Lucide Vue Next** | ^0.468 (icons) |
| **Heroicons Vue** | ^2.2 (icons) |
| **Laravel Echo** | ^2.3 (WebSocket client) |
| **Pusher.js** | ^8.4 (WebSocket transport) |
| **Stripe.js** | ^8.11 (client-side Stripe) |
| **vue-sonner** | ^2.0 (toast notifications) |
| **Vite** | ^7.0 |

### Build & Dev Tools
- **Vite** with `laravel-vite-plugin`, `@tailwindcss/vite`, `@vitejs/plugin-vue`, `@laravel/vite-plugin-wayfinder`
- **ESLint** + **Prettier** for linting and formatting
- **Laravel Pint** for PHP code style
- **PHPUnit** for backend tests
- **SSR support** available via `resources/js/ssr.ts`

---

## 3. Project Structure

```
Spotlight/
├── app/
│   ├── Actions/              # Single-purpose action classes
│   ├── Concerns/             # Traits and shared behaviors
│   ├── Console/              # Artisan commands (e.g., scheduled tasks)
│   ├── Enums/                # PHP 8.1 enums for statuses, roles, types
│   ├── Events/               # Laravel event classes (broadcasting)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/        # Admin-specific controllers
│   │   │   ├── Affiliate/    # Affiliate dashboard controller
│   │   │   ├── Api/          # API auth controller (AuthController)
│   │   │   ├── Auth/         # Password reset, Google OAuth controllers
│   │   │   ├── Settings/     # User settings controllers
│   │   │   ├── EvenementController.php      # Core event CRUD + management
│   │   │   ├── ReservationController.php    # Booking/cancellation logic
│   │   │   ├── StripeController.php         # Payment checkout + webhooks
│   │   │   ├── StripeConnectController.php  # Organizer Stripe onboarding
│   │   │   ├── MessageController.php        # Real-time messaging
│   │   │   ├── CollaborationController.php  # Event co-organizer system
│   │   │   ├── BilletController.php         # Ticket viewing/download
│   │   │   ├── MediaUploadController.php    # Image/video uploads to Cloudinary
│   │   │   ├── GenerateImagesController.php # AI image generation
│   │   │   └── ...
│   │   ├── Middleware/       # Custom middleware (role-based, blocked check, etc.)
│   │   └── Requests/        # Form request validation classes
│   ├── Mail/                 # Mailable classes
│   ├── Models/               # Eloquent models (see §4)
│   ├── Notifications/        # Laravel notification classes
│   ├── Observers/            # Model observers
│   ├── Providers/            # Service providers
│   └── Services/             # Business logic services
│       ├── AIReformulationService.php
│       ├── CategoryTranslationService.php
│       ├── CommissionService.php
│       ├── ExpoPushService.php
│       ├── NotificationService.php
│       └── TicketService.php
├── config/                   # Laravel config files + cloudinary.php, reverb.php, fortify.php
├── database/
│   ├── factories/            # Model factories for testing
│   ├── migrations/           # 55 migration files (see §4 for schema)
│   └── seeders/              # Database seeders
├── lang/                     # Server-side language files
├── resources/
│   ├── css/                  # Global CSS
│   ├── js/
│   │   ├── app.ts            # Vue app entry point
│   │   ├── ssr.ts            # SSR entry point
│   │   ├── echo.ts           # Laravel Echo config
│   │   ├── components/       # Vue components
│   │   │   ├── ui/           # shadcn-vue UI primitives
│   │   │   ├── auth/         # Auth-related components
│   │   │   ├── organizer/    # Organizer-specific components
│   │   │   ├── EventCard.vue
│   │   │   ├── Reserver.vue  # Reservation modal/form
│   │   │   ├── AppHeader.vue, AppFooter.vue, AppSidebar.vue
│   │   │   ├── NotificationsDropdown.vue
│   │   │   ├── MessagesDropdown.vue
│   │   │   ├── InvitationsDropdown.vue
│   │   │   └── ...
│   │   ├── composables/      # Vue composables (reusable logic)
│   │   ├── i18n/
│   │   │   ├── index.ts      # i18n plugin setup
│   │   │   └── locales/      # fr.json, en.json, ar.json
│   │   ├── layouts/
│   │   │   ├── AppLayout.vue   # Authenticated layout (sidebar)
│   │   │   ├── AuthLayout.vue  # Login/register layout
│   │   │   ├── app/            # Layout sub-components
│   │   │   ├── auth/
│   │   │   └── settings/
│   │   ├── pages/
│   │   │   ├── Welcome.vue           # Landing/home page
│   │   │   ├── Dashboard.vue         # Organizer dashboard
│   │   │   ├── Blocked.vue           # Blocked user page
│   │   │   ├── Admin/                # Admin panel pages
│   │   │   │   ├── Dashboard.vue
│   │   │   │   ├── Users/
│   │   │   │   ├── Events/
│   │   │   │   ├── Financials/
│   │   │   │   └── Reservations/
│   │   │   ├── Affiliate/            # Affiliate dashboard
│   │   │   ├── Events/
│   │   │   │   ├── Discovery.vue     # Public event browsing
│   │   │   │   ├── Show.vue          # Event detail page
│   │   │   │   ├── CreateEvent.vue   # Event creation form
│   │   │   │   ├── EditEvent.vue     # Event editing form
│   │   │   │   ├── EventsList.vue    # Organizer's event list
│   │   │   │   ├── Collaborations.vue
│   │   │   │   └── MyReservations.vue
│   │   │   ├── Messages/             # Chat pages
│   │   │   ├── Notifications/        # Notification center
│   │   │   ├── Organizer/            # Public organizer profile
│   │   │   ├── Participant/          # Public participant profile
│   │   │   ├── Billets/              # Ticket view/download pages
│   │   │   ├── Legal/                # About, Privacy, Terms
│   │   │   ├── auth/                 # Login, Register, VerifyEmail
│   │   │   └── settings/             # User settings pages
│   │   ├── types/                    # TypeScript interfaces
│   │   │   ├── event.ts              # Evenement, Media, Tournoi, Category interfaces
│   │   │   ├── auth.ts              # Auth-related types
│   │   │   ├── navigation.ts
│   │   │   └── index.ts
│   │   ├── lib/              # Utility functions (cn, etc.)
│   │   ├── routes/           # Wayfinder-generated route helpers
│   │   ├── wayfinder/        # Auto-generated Wayfinder files
│   │   └── actions/          # Wayfinder action helpers
│   └── views/                # Blade views (app.blade.php shell)
├── routes/
│   ├── web.php               # Main web routes (Inertia pages + web-api endpoints)
│   ├── api.php               # REST API routes (Sanctum-protected, for mobile app)
│   ├── channels.php          # Broadcasting channel authorization
│   ├── console.php           # Scheduled commands
│   └── settings.php          # Settings routes
├── public/                   # Public assets
├── storage/                  # Logs, cache, uploads
├── tests/                    # PHPUnit tests
├── composer.json             # PHP dependencies
├── package.json              # Node dependencies
├── vite.config.ts            # Vite build configuration
└── tsconfig.json             # TypeScript configuration
```

---

## 4. Data Models & Database Schema

### Entity Relationship Overview

```
User (1) ──── (0..1) Organisateur
User (1) ──── (0..1) Revendeur
User (1) ──── (N) Reservation
User (1) ──── (N) DeviceToken
User (1) ──── (N) Notification

Evenement (N) ──── (1) User (as organisateur_id)
Evenement (1) ──── (N) Reservation
Evenement (1) ──── (N) Media (polymorphic)
Evenement (1) ──── (0..1) Tournoi
Evenement (1) ──── (N) EventCollaborator
Evenement (N) ──── (0..1) Category

Reservation (1) ──── (0..1) Paiement
Reservation (1) ──── (N) Billet
Reservation (1) ──── (0..1) Commission
Reservation (N) ──── (0..1) Revendeur

Commission (N) ──── (0..1) Revendeur
```

### Core Models

#### `User`
| Field | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| username | string | |
| email | string (unique) | |
| password | string (hashed) | |
| telephone | string | |
| about | text | User bio |
| role | enum (`Role`) | `administrateur`, `organisateur`, `participant`, `revendeur` |
| statut | string | `actif`, `blocked` |
| interests | json (array) | User interests for recommendations |
| google_id, google_token, google_refresh_token | string | Google OAuth fields |
| blocked_until | datetime | Null = permanent block, future date = temporary |
| email_verified_at | datetime | |
| two_factor_secret, two_factor_recovery_codes, two_factor_confirmed_at | | Fortify 2FA |
| dateCreation | datetime | |

**Relationships**: `reservations()`, `paiements()` (through reservations), `organisateur()` (HasOne), `revendeur()` (HasOne)

**Key methods**: `isAdmin()`, `isParticipant()`, `isOrganisateur()`, `isRevendeur()`, `isBlocked()`, `bloquerCompte()`, `debloquerCompte()`, `validerEvenement()`, `reserverEvenement()`, `payerReservation()`

#### `Evenement` (Event)
| Field | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| organisateur_id | FK → users.id | The organizer who created the event |
| titre | string | Event title |
| description | text | |
| date_debut, date_fin | datetime | Start and end dates |
| lieu | string | Location/venue |
| prix_spectateur | double | Spectator ticket price |
| capacite_spectateur | integer | Max spectators |
| statut | enum (`StatutEvenement`) | See below |
| categorie | string | Legacy category field |
| categorie_autre | string | Custom category text |
| category_id | FK → categories.id | New translatable category |
| is_tournoi | boolean | Whether this is a tournament |
| type_tournoi | string | `equipe` or `individuel` |
| prix_participant | decimal | Tournament participant price |
| capacite_participant | integer | Max tournament participants |
| poster_url, poster_public_id | string | Cloudinary poster image |
| video_url, video_public_id | string | Cloudinary video |
| titre_image | string | Image title/alt text |
| is_paid_out | boolean | Whether organizer has been paid |
| paid_out_at | datetime | |
| is_reminder_sent | boolean | Whether reminder notification sent |

**Event Statuses** (`StatutEvenement`):
- `en_attente` → Newly created, awaiting admin approval
- `encours` → Approved, in progress
- `ouvert` → Reservations open
- `ferme` → Reservations closed
- `valide` → Validated by admin
- `annule` → Cancelled
- `rejete` → Rejected by admin

**Relationships**: `organisateur()`, `reservations()`, `medias()` (polymorphic), `tournoi()`, `collaborateurs()`, `category()`

**Key methods**: `hasAvailableSpots()`, `isReservedBy()`, `isManagedBy()`, `ouvrirReservation()`, `fermerReservation()`

**Scopes**: `parOrganisateur()`, `parTitre()`, `parDate()`, `parPrix()`, `parCategorie()`, `parStatut()`

#### `Reservation`
| Field | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| user_id | FK → users.id | The participant |
| evenement_id | FK → evenements.id | |
| ticket_type | string | `spectator` or `participant` (for tournaments) |
| statut | enum (`StatutReservation`) | `pending`, `confirmed`, `cancelled` |
| nombre_tickets | integer | Number of tickets |
| note | text | Optional note |
| revendeur_id | FK → revendeurs.id | If booked via affiliate link |

**Lifecycle hooks** (in `booted()`):
- On **created**: Commission is auto-created, tickets generated if confirmed
- On **updated to confirmed**: Tickets generated via `TicketService`
- On **updated to cancelled**: Associated commission is reversed

#### `Organisateur` (Organizer Profile)
| Field | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| user_id | FK → users.id | |
| nom_organisation | string | Organization name |
| description | text | |
| telephone, adresse, site_web, logo | string | Profile details |
| stripe_account_id | string | Stripe Connect account |

**Auto-created** when a user is assigned the `organisateur` role.

#### `Revendeur` (Reseller/Affiliate)
| Field | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| user_id | FK → users.id | |
| referral_code | string (8 chars) | Unique referral code |
| stripe_account_id | string | For payouts |
| balance | decimal | Accumulated earnings |

**Auto-created** when a user is assigned the `revendeur` role.

#### `Commission`
| Field | Type | Notes |
|---|---|---|
| id | bigint (PK) | |
| evenement_id | FK | |
| reservation_id | FK | |
| revendeur_id | FK (nullable) | |
| commission_organisateur | decimal | 80% of total |
| commission_admin | decimal | 20% (no affiliate) or 15% (with affiliate) |
| commission_revendeur | decimal | 0% (no affiliate) or 5% (with affiliate) |
| status | enum (`StatutCommission`) | `pending`, `approved`, `reversed` |
| stripe_transfer_id | string | Stripe transfer reference |

#### Other Models
- **`Billet`** — Individual ticket with QR code, linked to a reservation
- **`Paiement`** — Payment record linked to a reservation (Stripe session/payment intent IDs)
- **`Media`** — Polymorphic media (image/video) with Cloudinary URLs
- **`Tournoi`** — Tournament details (nombre_equipes, joueurs_par_equipe, etc.)
- **`Message`** — Chat messages between users
- **`Notification`** — In-app notifications with type enum
- **`EventCollaborator`** — Co-organizer invitations with granular permissions
- **`Category`** — Translatable event categories (via Spatie Translatable)
- **`DeviceToken`** — Push notification device tokens (for mobile)

---

## 5. Roles & Authorization

| Role | Value | Capabilities |
|---|---|---|
| **Admin** (`administrateur`) | Full platform control | Approve/reject events, manage users, block/unblock, financial management, create reservations |
| **Organizer** (`organisateur`) | Event management | Create/edit/cancel events, manage reservations, view stats, invite collaborators, Stripe Connect onboarding |
| **Participant** (`participant`) | Event consumption | Browse/search events, make reservations, pay via Stripe, view tickets, messaging |
| **Reseller** (`revendeur`) | Affiliate marketing | Generate referral links, track commissions, view earnings dashboard |

### Middleware
- `auth` — Laravel session authentication
- `auth:sanctum` — API token authentication (mobile)
- `role:administrateur` — Admin-only routes
- `role:organisateur` — Organizer-only routes
- `role:revendeur` — Reseller-only routes

---

## 6. Routing Architecture

The app uses a **dual routing strategy**:

### Web Routes (`routes/web.php`)
- **Inertia pages**: Rendered via `Inertia::render()` for the Vue SPA
- **`web-api/` prefix**: JSON API endpoints that use session authentication (for the Inertia frontend). These handle CRUD operations, Stripe payments, messaging, notifications, collaboration, AI features, etc.
- **Role-scoped sections**:
  - `/admin/*` — Admin dashboard and management (middleware: `role:administrateur`)
  - `/dashboard/*` — Organizer dashboard (middleware: `role:organisateur`)
  - `/affiliate/dashboard` — Affiliate dashboard (middleware: `role:revendeur`)

### API Routes (`routes/api.php`)
- **Sanctum-protected**: Token-based auth for mobile app consumption
- Mirrors key web-api functionality (events, reservations, messages, organizer management)
- Includes admin endpoints under `admin` middleware
- **Public**: Event listing/search, categories, device token management
- **Stripe webhook**: `POST /api/stripe/webhook`

### Key Route Patterns
- Events: `/events/{id}` (public show), `/web-api/events` (CRUD)
- Reservations: `/web-api/reservations`, `/web-api/my-reservations`
- Payments: `/web-api/paiement/checkout/{reservation}`
- Messages: `/messages`, `/messages/{user}`, `/web-api/messages`
- Notifications: `/web-api/notifications`
- Collaboration: `/web-api/events/{id}/collaborators/*`
- Media: `/upload/image`, `/upload/video`
- Tickets: `/tickets/{billet}`, `/tickets/{billet}/download`

---

## 7. Business Logic & Services

### Commission System (`CommissionService`)
- **Without affiliate**: Organizer 80%, Admin 20%
- **With affiliate**: Organizer 80%, Admin 15%, Reseller 5%
- Commissions are created automatically on reservation creation
- Status: `pending` → `approved` (by admin) or `reversed` (on cancellation)
- Reseller balance is auto-incremented/decremented on status changes

### Ticket System (`TicketService`)
- Generates `Billet` records with unique QR codes on reservation confirmation
- PDF download available via `BilletController` using DomPDF

### Notification System (`NotificationService`)
- Supports multiple notification types: reservation updates, collaboration invites, payment confirmations, event reminders, etc.
- `ExpoPushService` for mobile push notifications

### AI Features (`AIReformulationService`, `GenerateImagesController`)
- Event description reformulation/enhancement
- AI-powered event image generation and suggestions

### Category Translation (`CategoryTranslationService`)
- Uses Spatie Translatable for multi-language category labels
- Auto-translation via Google Translate PHP library

---

## 8. Frontend Architecture

### Rendering: Inertia.js
- Server renders pages via `Inertia::render('PageName', $props)`
- Client hydrates Vue components in `resources/js/pages/`
- Props are passed from Laravel controllers directly to Vue page components

### State Management
- **Inertia shared data**: Auth user, flash messages (via `HandleInertiaRequests` middleware)
- **Pinia stores**: For complex client-side state
- **Composables**: Reusable logic in `resources/js/composables/`

### Component Library
- **shadcn-vue** (built on `reka-ui`): Provides accessible UI primitives in `resources/js/components/ui/`
- **Styling**: Tailwind CSS v4 with `class-variance-authority` and `tailwind-merge`

### Layouts
- `AppLayout.vue` — Main authenticated layout with sidebar navigation
- `AuthLayout.vue` — Minimal layout for login/register pages

### Key Frontend Patterns
- TypeScript throughout with interfaces in `resources/js/types/`
- `<script setup lang="ts">` syntax for all Vue components
- Path alias: `@` maps to `resources/js/`
- Wayfinder generates type-safe route helpers in `resources/js/wayfinder/`

---

## 9. External Services & Configuration

| Service | Purpose | Config |
|---|---|---|
| **Stripe** | Payments (Checkout), Organizer payouts (Connect) | `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` |
| **Cloudinary** | Image/video uploads and hosting | `CLOUDINARY_URL`, `CLOUDINARY_CLOUD_NAME`, etc. |
| **Google OAuth** | Social login | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT` |
| **Laravel Reverb** | WebSocket server for real-time features | `REVERB_APP_ID`, `REVERB_APP_KEY`, etc. |
| **Pusher** | WebSocket transport (used with Reverb) | `PUSHER_APP_ID`, `PUSHER_APP_KEY`, etc. |
| **Mail** | Email verification, password reset, notifications | Standard Laravel mail config |

---

## 10. Development Commands

```bash
# Install dependencies
composer install
npm install

# Run the full dev stack (server + queue + logs + Vite)
composer dev

# Run with SSR
composer dev:ssr

# Build for production
npm run build

# Lint PHP
composer lint

# Lint/format JS/Vue
npm run lint
npm run format

# Run tests
composer test

# Generate IDE helper
php artisan ide-helper:generate
```

---

## 11. Naming Conventions

### Language
The codebase uses a **mix of French and English**:
- **French**: Model names (`Evenement`, `Organisateur`, `Revendeur`, `Billet`, `Paiement`), database columns (`titre`, `lieu`, `prix_spectateur`, `capacite_spectateur`, `statut`, `nombre_tickets`), method names (`ouvrirReservation`, `fermerReservation`, `annuler`, `envoyer`), enum values
- **English**: Framework conventions (controllers, middleware, services), TypeScript types, status enum values (`pending`, `confirmed`, `cancelled`), Stripe-related fields

### Code Style
- **PHP**: PSR-12 via Laravel Pint
- **Vue/TS**: Prettier + ESLint
- **Models**: Use PHP 8.1 enums for status fields with `casts()` method
- **Controllers**: Resourceful naming where possible
- **Routes**: RESTful conventions with French method names

### Database
- Table names are **French plural**: `evenements`, `organisateurs`, `reservations`, `paiements`, `billets`, `revendeurs`, `commissions`, `tournois`
- Foreign keys follow Laravel convention: `user_id`, `evenement_id`, etc.
- Timestamps: Laravel default `created_at`/`updated_at`

---

## 12. Important Gotchas & Notes

1. **Dual auth system**: Web routes use session auth, API routes use Sanctum tokens. The `web-api/` prefix routes are JSON endpoints but run under the web middleware group (session auth) — they exist specifically for the Inertia frontend.

2. **Event ownership check**: `Evenement::isManagedBy()` checks both direct ownership AND accepted collaborator status with optional permission granularity. Admins always pass.

3. **Commission auto-creation**: Commissions are created in `Reservation::booted()` via `CommissionService::createForReservation()` — they are NOT created by controllers directly.

4. **Ticket generation**: Tickets (`Billet`) are auto-generated in `Reservation::booted()` when status changes to `confirmed` — via `TicketService::generate()`.

5. **Organizer/Revendeur auto-creation**: When a user's role is set to `organisateur` or `revendeur`, the corresponding profile model is auto-created in `User::booted()`.

6. **Category system migration**: The project migrated from a simple enum-based category (`categorie` column) to a translatable `Category` model (`category_id`). Both fields exist; the new `category_id` is preferred.

7. **Blocked users**: A blocked user sees the `/blocked` page. Block can be permanent (`blocked_until = null`) or temporary (`blocked_until = future date`).

8. **Stripe Connect flow**: Organizers onboard via `/web-api/stripe/connect` → Stripe hosted onboarding → return to `/stripe/connect/return`. Their `stripe_account_id` is stored on the `Organisateur` model.

9. **Real-time messaging**: Uses Laravel Reverb as the WebSocket server, with Pusher.js as the client transport and Laravel Echo for channel subscriptions. Configured in `resources/js/echo.ts`.

10. **SSR**: The project supports Server-Side Rendering via `resources/js/ssr.ts`, buildable with `npm run build:ssr` and runnable with `php artisan inertia:start-ssr`.
