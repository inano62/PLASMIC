# PLASMIC – AI Coding Agent Instructions

**PLASMIC** is a full-stack SaaS platform for online booking and payments, targeting service professionals (lawyers, doctors, beauty clinics). Two-tier architecture: **WordPress LP** (fast iteration) + **React/Laravel API** (core platform).

---

## Architecture Overview

### Stack
- **Frontend**: React 19 + Vite + Tailwind CSS (TypeScript, port 5176)
- **Backend**: Laravel 12 + PHP 8.2 + Sanctum auth (port 8000)
- **Real-time Communication**: LiveKit (WebRTC video rooms, port 7880)
- **Payment**: Stripe API & Stripe Connect
- **Database**: MySQL 8.0 (containerized, port 3307)
- **Infrastructure**: Docker Compose + Nginx

### Core Entities (Database Layer)
[Database schema](www/database/migrations/0001_01_01_000000_full_schema.php):
- **users**: role = {admin, owner, lawyer, client}; Stripe customer tracking (stripe_customer_id, subscription_status)
- **tenants**: Multi-tenant support (slug-based); stores Stripe Connect ID for future marketplace
- **tenant_users**: Join table (user roles within tenant: staff, owner, etc.)
- **appointments**: Core booking model; links client → professional; tracks room_name for LiveKit
- **timeslots**: Availability windows for professionals
- **reservations**: Pre-payment state (cart); converts to appointment after Stripe checkout
- **sites**: CMS pages; blocks are draggable components for public-facing landing pages
- **media**: File uploads (referenced in site blocks)

### Service Boundaries

**Public API** (`/api/public/*`):
- Tenant discovery, site pages, appointment creation
- No authentication required
- Used by: landing pages, booking widgets

**Admin API** (`/api/admin/*`):
- Site builder (drag-drop blocks), media upload
- **Protected** by `auth:sanctum` middleware
- Used by: React admin dashboard

**Appointment/Billing Flow**:
1. Client creates appointment/reservation via public API → `Reservation` model
2. Frontend initiates Stripe checkout via `/api/pay/checkout/{id}`
3. Stripe webhook (`/api/stripe/webhook`) converts reservation → `Appointment`
4. Backend issues LiveKit token → client connects to video room

**LiveKit Integration** (`/api/video/token`):
- Issues JWT tokens for room access
- Token sources: ticket JWT (has room in `sub` claim), appointment ID, or direct room name
- Validates against LIVEKIT_API_KEY / LIVEKIT_API_SECRET
- Room access enforced by LiveKit server (external service on ws://localhost:7880)

---

## Developer Workflows

### Local Setup
```bash
# Database + services
docker compose up -d

# Laravel migrations + seeds
docker compose exec app php artisan migrate:fresh --seed

# Frontend dev server (http://localhost:5176)
docker compose logs -f next

# Check DB (http://localhost:8082)
docker compose logs -f phpmyadmin
```

### Testing Token Generation
LiveKit tokens are **critical**. Test via:
```powershell
# Issue token
$tok = Invoke-RestMethod -Method Post `
  -Uri http://localhost:8000/api/dev/token `
  -ContentType 'application/json' `
  -Body (@{ room='r_test'; identity='test_user' } | ConvertTo-Json)

# Validate (should return 200)
(Invoke-WebRequest -UseBasicParsing -Uri ("http://127.0.0.1:7880/rtc/validate?access_token=" + $tok.token)).StatusCode
```

### Common Commands
```bash
# Clear caches (required after config changes)
php artisan config:clear && php artisan optimize:clear

# Generate IDE helpers (better autocomplete)
php artisan ide-helper:generate

# Watch for changes (Tailwind)
npm run watch  # in frontend/
```

---

## Code Patterns & Conventions

### Frontend (React + TypeScript)

**API Communication** ([api.ts](frontend/src/lib/api.ts)):
- Axios instance (`api`) with **Bearer token** in Authorization header
- Token stored in localStorage under key `'token'`
- 401 responses trigger automatic token deletion
- Use `api.get()`, `api.post()`, `api.put()` directly; no wrapper needed unless special handling

**Routing** (React Router v7):
```tsx
// main.tsx defines all routes
// Public: Join, Wait, Host (LiveKit pages)
// Protected: Admin dashboard under /admin
// Multi-tenant: /:tenant/reserve → ReservePage component
```

**Vite Proxy Config** ([vite.config.ts](frontend/vite.config.ts)):
- `/api/*` proxies to `http://localhost:8000` (Laravel backend)
- `/sanctum/*` proxies to same backend (CSRF cookies)
- Logging enabled on proxy requests/responses

**Components by Responsibility**:
- `/components/admin/*`: Admin dashboard widgets
- `/components/site/*`: Site builder UI
- `/components/site-builder/*`: Drag-drop block editor
- `/pages/*`: Route components

### Backend (Laravel)

**Controllers** ([Http/Controllers/](www/app/Http/Controllers/)):
- Grouped by domain: AppointmentController, StripeController, LivekitController
- Follow standard CRUD: `store()`, `show()`, `update()`, `index()`
- Return JSON directly (no view layer in API routes)

**Middleware**:
- `auth:sanctum` for protected routes; injects authenticated user via `$request->user()`
- Custom middleware in `Http/Middleware/`

**Models** ([Models/](www/app/Models/)):
- Use **Eloquent** ORM exclusively
- Foreign key conventions: `{entity}_user_id`, `{entity}_id`
- Key models: `Appointment`, `Reservation`, `User`, `Tenant`, `Timeslot`

**Example Pattern** ([AppointmentController](www/app/Http/Controllers/AppointmentController.php)):
```php
public function storeForTenant(Request $request, $tenant) {
    $apt = Appointment::create([
        'tenant_id' => Tenant::where('slug', $tenant)->id,
        'client_user_id' => $request->user()->id,
        'room_name' => 'room_' . Str::uuid(),
    ]);
    return $apt->toArray();
}
```

**Stripe Integration** ([StripeController](www/app/Http/Controllers/StripeController.php)):
- Uses `stripe/stripe-php` SDK
- Checkout creates payment intent
- Webhook listener at `/api/stripe/webhook` converts reservation → appointment on success
- Stripe customer ID stored on `User` model

**LiveKit Token Issuance** ([LivekitController](www/app/Http/Controllers/LivekitController.php)):
- JWT signed with LIVEKIT_API_SECRET
- Token claims: `jti`, `iss` (API key), `sub` (identity), `exp`, `video` (grants)
- Environment vars: LIVEKIT_API_KEY, LIVEKIT_API_SECRET, LIVEKIT_WS_URL

### Tenancy Model
- **Single database**, **slug-based routing** (not domain-based)
- Queries often filter by tenant: `Appointment::where('tenant_id', $tenantId)`
- Tenant owner linked via `Tenant.owner_user_id`
- Staff members joined via `TenantUser` pivot

---

## Critical Integration Points

### Frontend ↔ Backend Communication
- **Axios Bearer Token**: stored in localStorage, auto-injected on all `/api` requests
- **CSRF Protection**: Sanctum handles CSRF via `/sanctum/csrf-cookie` endpoint (unused in current Bearer-only setup)
- **Error Handling**: 401 responses clear token; non-2xx responses throw

### Stripe Webhook
- **URL**: `/api/stripe/webhook` (no auth required, signed by Stripe)
- **Trigger**: Payment success → issue webhook
- **Logic**: Find reservation, create appointment, return 200 OK
- **Secret**: STRIPE_WEBHOOK_SECRET environment variable (verify signature before processing)

### LiveKit Room Flow
1. Frontend requests token via `/api/video/token` (POST) or dev endpoint `/api/dev/token`
2. Backend issues JWT with room name in `sub` claim
3. Frontend connects `livekit-client` library to LIVEKIT_WS_URL with token
4. LiveKit validates token against configured API key/secret
5. Backend logs room events via `/api/calls/event` (optional tracking)

### Multi-Tenant Site Rendering
- Site pages fetched via `/api/public/sites/{slug}`
- Returns JSON with blocks (title, content, media_id)
- Frontend renders as React component tree
- Media IDs resolved via `/api/admin/media/{id}`

---

## Project Specific Gotchas & Conventions

1. **Database Timezone**: Explicitly set to Asia/Tokyo in docker-compose.yml and PHP config
2. **Token Secrets**: Multiple secret keys in use:
   - `TICKET_SECRET`: JWT for appointment booking confirmation
   - `LIVEKIT_API_SECRET`: LiveKit token signing
   - `STRIPE_WEBHOOK_SECRET`: Stripe signature verification
   - `APP_KEY`: Laravel encryption key
3. **Image Uploads**: Routed through `/api/admin/media` → stored in Docker volume `laravel-storage` → served via `/storage/` path
4. **Migrations**: Schema is monolithic ([0001_01_01_000000_full_schema.php](www/database/migrations/0001_01_01_000000_full_schema.php)); run `migrate:fresh --seed` for test data
5. **Development Passwords**: Hardcoded in docker-compose (user=plasmic, pass=plasmic); DO NOT push to production
6. **Frontend Build Output**: Vite builds to `www/public/dist/` (Laravel serves as static assets)
7. **LiveKit Dev Mode**: Runs without authentication on port 7880; requires explicit `/rtc/validate` call to verify token

---

## When Adding Features

- **New endpoint?** Add to [routes/api.php](www/routes/api.php), create controller, add middleware if auth required
- **New model?** Create migration, add to Models/, setup relationships in `boot()` method
- **Frontend UI?** Use Tailwind (no build step), place in `/components` or `/pages`, hook to API via axios
- **Database query takes too long?** Add `.index()` to migration; check `/api/tenants/{tenant}` routes for examples of optimized queries with `select()` and eager loading
- **Introducing job queue?** Use Laravel Queue; job definitions in `app/Jobs/`, dispatch via `dispatch()` helper
- **Real-time features?** Use LiveKit + socket events via `/api/calls/event` (already integrated)

---

## External Dependencies & Versions

| Package | Purpose | Notes |
|---------|---------|-------|
| Laravel 12 | API framework | Modern, Sanctum for auth |
| React 19 | Frontend | Vite for HMR, Tailwind for styling |
| Stripe SDK | Payments | Webhook-driven state machine |
| LiveKit SDK | Video | WebRTC, handles NAT traversal |
| Axios | HTTP client | Auto-config Bearer tokens |
| React Router v7 | Routing | Nested routes, slug-based tenancy |

---

## Questions for Clarification

Before you implement changes, confirm:
1. **Multi-tenant scope**: Should this affect all tenants or one specific tenant?
2. **Authentication**: Is this admin-only (protected by `auth:sanctum`) or public?
3. **Real-time**: Does this require LiveKit integration or is polling/HTTP sufficient?
4. **Database**: Are you adding a new entity or extending existing ones (e.g., User relationships)?
