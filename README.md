# AutoCare - Vehicle Maintenance Manager

A personal garage management web app. Users register their vehicles, record services and repairs, track fuel expenses and monitor upcoming maintenance.

> Status: Milestone 1 (static frontend and draft ERD). Backend is added in later milestones.

## Tech stack

- Frontend: HTML, CSS, JavaScript, Bootstrap 5, Bootstrap Icons, Chart.js (single-page app, hash routing)
- Backend (planned): PHP, FlightPHP, PDO, MySQL, JWT
- Docs (planned): OpenAPI / Swagger

## Entities

`users`, `vehicles`, `service_types`, `service_records`, `fuel_logs`, `reminders`. See [docs/ERD.md](docs/ERD.md).

## Roles and test accounts

| Role | What they can do | Email | Password |
|---|---|---|---|
| user | Full CRUD on their own vehicles, services, fuel logs and reminders; read-only service types | TBD in M4 | TBD in M4 |
| admin | Everything above, plus manage users and service types, and view all data | TBD in M4 | TBD in M4 |

## External API

NHTSA vPIC / Recalls API (no key required):
- VIN decoder fills make, model and year in the "Add vehicle" form.
- Recalls tab on the vehicle details page lists open recalls for the vehicle.

## Run locally

Frontend only (Milestone 1). Views are loaded with `fetch`, so use a local web server instead of opening the file directly:

```bash
php -S localhost:8000 -t frontend
# or: python3 -m http.server 8000 --directory frontend
```

Open http://localhost:8000 and use the sidebar. All data on the pages is static demo data.

Backend setup (from Milestone 2): import the `.sql` file into MySQL and copy `backend/config/config.example.php` to `backend/config/config.php`, then fill in your values.

## Project structure

```
backend/   routes, services, dao, middleware, config, logs
frontend/  index.html, css, js, views, assets
docs/      ERD.md, AI_LOG.md
```

## Links

- Swagger documentation: TBD (Milestone 3)
- Deployed application: TBD (Milestone 5)
