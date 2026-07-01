# Fisio API

REST API for managing a physiotherapy clinic, built with **Laravel 12** and **Laravel Sanctum**. It handles users with different roles (administrator, therapist and patient), treatments, rooms, appointments, medical histories and session vouchers (*bonos*).

## Table of contents

- [About the project](#about-the-project)
- [Tech stack](#tech-stack)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Running the app](#running-the-app)
- [Authentication & roles](#authentication--roles)
- [Demo mode](#demo-mode-read-only)
- [Main endpoints](#main-endpoints)
- [Data model](#data-model)

## About the project

FisioWorks started as an **end-of-course final project**, but it was never meant to be a throwaway exercise: it was designed around the **real needs of a small physiotherapy clinic**. The requirements were gathered directly in conversation with the clinic's owner, and the application was tailored to how the clinic actually operates day to day.

The system is **in production**, running on the client's own server (which can't be named here for privacy reasons).

The project is deliberately split into a separate **API** (this repository) and **[frontend](https://github.com/madawgg/fisioWorks_front)**. This separation is by design: it keeps the backend independent so the same API can power additional clients in the future — for example a **mobile app** or a **desktop application** — without rewriting the business logic.

> ⚠️ **Note:** This public repository is **demonstrative only** and does **not** reflect the current state of the production code. It has been intentionally simplified to protect the security of the live system and the client's data. Treat it as a portfolio showcase, not the deployed source.

## Tech stack

- **PHP** ^8.2
- **Laravel** ^12.0
- **Laravel Sanctum** ^4.0 (token-based authentication)
- **MySQL** (the availability queries use MySQL-specific SQL)
- **Vite** for frontend assets

## Features

- Token-based authentication with Laravel Sanctum.
- **Role** system (admin, therapist, patient) handled through custom middleware.
- Full CRUD for users, treatments, rooms, specialties, appointments, medical histories and vouchers.
- **Soft deletes** and record restoration (users, vouchers, patient vouchers).
- Appointment booking logic: computes **free hours**, **free therapists** and **free rooms** while avoiding overlaps.
- Session **voucher** system: purchase, automatic session consumption when booking and refund on cancellation.

## Requirements

- PHP >= 8.2
- Composer
- Node.js & npm
- MySQL

## Installation

```bash
# Clone the repository
git clone <repository-url>
cd fisio_copia

# Install dependencies, copy .env, generate the key, migrate and build assets
composer setup
```

The `composer setup` script runs:

```bash
composer install
cp .env.example .env        # if it doesn't exist
php artisan key:generate
php artisan migrate --force
npm install
npm run build
```

Before migrating, set your database credentials in `.env` (`DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`).

To load sample data (seeders):

```bash
php artisan migrate:fresh --seed
```

## Running the app

Full development environment (server, queues, logs and Vite):

```bash
composer dev
```

Or just the API server:

```bash
php artisan serve
```

The API is available at `http://localhost:8000/api`.

## Authentication & roles

Authentication uses Sanctum **Bearer tokens**. After logging in you get a `token` that must be sent with every protected request:

```
Authorization: Bearer <token>
```

Available roles, enforced by middleware:

| Middleware         | Access                          |
|--------------------|---------------------------------|
| `auth:sanctum`     | Any authenticated user          |
| `admin`            | Administrators only             |
| `adminOrTherapist` | Administrators or therapists    |
| `patient`          | Patients only                   |

A user can hold several roles at once. On registration, a user is automatically created as a **patient**.

## Demo mode (read-only)

The API ships with a **read-only demo mode** so anyone can explore the whole system without registering or being able to modify data — ideal for a "Try the demo" button on the frontend.

**How it works**

1. `POST /demo-login` logs into a pre-seeded demo account (admin role, so it can read everything) and returns a Sanctum token issued with the **`demo`** ability — no credentials required.
2. The `DemoReadOnly` middleware (aliased `demo.readonly`, applied to the whole authenticated group) allows safe methods (`GET`/`HEAD`/`OPTIONS`) and `logout`, but rejects any write (`POST`/`PUT`/`PATCH`/`DELETE`) with a `403`:

```json
{ "status": "error", "message": "Modo demo: solo lectura. Esta acción está deshabilitada." }
```

Regular tokens are issued with the `*` ability and are never affected — only the demo token carries the literal `demo` ability that the middleware checks for. The data is therefore protected at the API level, regardless of what the client does.

**Hardening against abuse**

- The demo token is **short-lived** (expires in 10 minutes).
- `POST /demo-login` is **rate-limited** to 5 requests/minute per IP (named limiter in `AppServiceProvider`), and the whole API to 120/minute per IP, to prevent mass token creation / DoS.
- Expired tokens are cleaned up daily via `sanctum:prune-expired` (scheduled in `routes/console.php`; requires the scheduler cron in production).

**Setup**

The demo account is created by `DemoSeeder` (`demo@fisio.test`), run automatically by `php artisan db:seed`, or on its own:

```bash
php artisan db:seed --class=DemoSeeder
```

| Component | Location |
|-----------|----------|
| Endpoint  | `POST /demo-login` → `UserController::demoLogin` |
| Middleware| `app/Http/Middleware/DemoReadOnly.php` |
| Seeder    | `database/seeders/DemoSeeder.php` |

## Main endpoints

> Base prefix: `/api`

### Authentication (public)

| Method | Endpoint      | Description                                            |
|--------|---------------|--------------------------------------------------------|
| POST   | `/login`      | Logs in and returns the access token                   |
| POST   | `/register`   | Registers a user (created as a patient)                |
| POST   | `/demo-login` | Logs into the read-only demo account (no credentials)  |
| POST   | `/logout`     | Logs out (revokes the current token)                   |

**Example — Login**

```http
POST /api/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "secret"
}
```

Response:

```json
{
  "user": { "id": 1, "name": "...", "email": "..." },
  "token": "1|abcdef..."
}
```

### Authenticated user profile

| Method | Endpoint                           | Description                                  |
|--------|------------------------------------|----------------------------------------------|
| GET    | `/users/me`                        | Authenticated user's profile with relations  |
| PATCH  | `/users/me`                        | Updates the authenticated user's own data    |
| GET    | `/current-user/roles`              | Authenticated user's roles                   |
| GET    | `/user`                            | Authenticated user with patient & therapist  |
| GET    | `/appointments/me`                 | Authenticated patient's appointments         |
| GET    | `/appointments/me/{appointmentId}` | Details of one of the user's own appointments|
| GET    | `/medical-histories/patient/me`    | Authenticated patient's medical history      |

### Appointments

| Method    | Endpoint                                           | Description                                       |
|-----------|----------------------------------------------------|---------------------------------------------------|
| GET       | `/appointments`                                    | Lists all appointments (formatted with relations) |
| POST      | `/appointments`                                    | Creates an appointment                            |
| GET       | `/appointments/{id}`                               | Appointment details                               |
| PUT/PATCH | `/appointments/{id}`                               | Updates an appointment (refunds voucher on cancel)|
| DELETE    | `/appointments/{id}`                               | Deletes an appointment                            |
| POST      | `/purchase-appointment`                            | Books/buys an appointment (patient)               |
| GET       | `/free-hours/{therapist_id}/{date}/{treatment_id}` | Free hours of a therapist for a given date        |
| GET       | `/free-therapists?start=...&duration=...`          | Free therapists within a time slot                |
| GET       | `/empty-rooms?start=...&duration=...`              | Free rooms within a time slot                     |

**Example — Book an appointment**

```http
POST /api/purchase-appointment
Authorization: Bearer <token>
Content-Type: application/json

{
  "therapist_id": 2,
  "room_id": 1,
  "treatment_id": 3,
  "appointment_date": "2026-07-01 10:00:00",
  "patient_bono_id": 5
}
```

### Vouchers (Bonos)

| Method    | Endpoint                          | Description                       |
|-----------|-----------------------------------|-----------------------------------|
| GET       | `/bonos`                          | Lists available vouchers          |
| POST      | `/bonos`                          | Creates a voucher                 |
| GET       | `/bonos/{id}`                     | Voucher details                   |
| PUT/PATCH | `/bonos/{id}`                     | Updates a voucher                 |
| DELETE    | `/bonos/{id}`                     | Deletes a voucher (soft delete)   |
| GET       | `/bonos-with-trashed` *(admin)*   | Lists including deleted vouchers  |
| PATCH     | `/bonos/{bono}/restore` *(admin)* | Restores a deleted voucher        |

### Patient vouchers (Patient Bonos)

| Method | Endpoint                                         | Description                                    |
|--------|--------------------------------------------------|------------------------------------------------|
| GET    | `/patient-bonos`                                 | Lists vouchers assigned to patients            |
| POST   | `/buy-bono`                                       | Assigns/buys a voucher for a patient           |
| GET    | `/patient-bonos/patient/{patientId}`             | A patient's vouchers                           |
| GET    | `/patient-bonos/active/patient/{patientId}`      | Active vouchers (with sessions, not expired)   |
| PATCH  | `/patient-bonos/{patientBono}/restore` *(admin)* | Restores a deleted patient voucher             |

### Users (admin / therapist)

| Method    | Endpoint                                          | Description                       |
|-----------|---------------------------------------------------|-----------------------------------|
| GET       | `/users`                                          | Lists all users                   |
| POST      | `/users`                                          | Creates a user                    |
| GET       | `/users/{id}`                                     | User details with relations       |
| PUT/PATCH | `/users/{id}`                                     | Updates a user                    |
| DELETE    | `/users/{id}`                                     | Deletes a user                    |
| GET       | `/users-roles`                                    | Users with their roles            |
| GET       | `/users/{userId}/roles`                           | A user's roles                    |
| PATCH     | `/users/{user}/restore` *(admin)*                 | Restores a deleted user           |
| POST      | `/add-patient-to-therapist/{userId}` *(admin)*    | Promotes a patient to therapist   |
| POST      | `/change-therapist-to-patient/{userId}` *(admin)* | Converts a therapist to patient   |

### Other resources (admin / therapist)

| Resource          | `apiResource` endpoints                  |
|-------------------|------------------------------------------|
| Patients          | `GET/POST/PUT/DELETE /patients`          |
| Therapists        | `GET/POST/PUT/DELETE /therapists`        |
| Treatments        | `GET/POST/PUT/DELETE /treatments`        |
| Rooms             | `GET/POST/PUT/DELETE /rooms`             |
| Specialties       | `GET/POST/PUT/DELETE /specialties`       |
| Medical histories | `GET/POST/PUT/DELETE /medical-histories` |
| Admins *(admin)*  | `GET/POST/PUT/DELETE /admins`            |

Additional related endpoints:

| Method | Endpoint                               | Description                  |
|--------|----------------------------------------|------------------------------|
| GET    | `/patient/{id}/appointments`           | A patient's appointments     |
| GET    | `/therapist/{id}/appointments`         | A therapist's appointments   |
| GET    | `/medical-histories/patient/{patient}` | A patient's medical history  |

> The `apiResource` endpoints expose the standard `index`, `store`, `show`, `update` and `destroy` actions.

## Data model

Main entities and their relationships:

- **User** — personal data (`dni`, `name`, `surname`, `birthdate`, `phone`, `address`, `email`). Can be `admin`, `therapist` and/or `patient`.
- **Patient / Therapist / Admin** — role profiles linked to a `User`.
- **Treatment** — `name`, `description`, `price`, `duration` (minutes).
- **Room** — `name`, `equipment`, `place`.
- **Specialty** — specialties, related to therapists (N:M).
- **Appointment** — `appointment_date`, `duration`, `status` (`pending`, `scheduled`, `completed`, `cancelled`), `is_paid`; linked to a patient, therapist, treatment, room and optionally a voucher.
- **Bono** — voucher template: `name`, `price`, `sessions`, `session_duration`.
- **PatientBono** — voucher assigned to a patient: `sessions_total`, `sessions_used`, `sessions_remaining`, `purchase_date`, `expiration_date`.
- **MedicalHistory** — clinical notes linking a patient and a therapist.

---

> Built with Laravel. Configure your `.env` (database, mail, etc.) before deploying to production.
