# AGENTS.md - AutoCare

Context and rules for AI coding assistants working in this repository.

## Project
AutoCare is a vehicle maintenance manager: users register vehicles, log services/repairs and fuel, and track reminders. University project (single-page app + REST API). The owner must be able to explain every line, so keep code simple and readable.

## Tech stack
- Frontend: plain HTML, CSS, JavaScript, Bootstrap 5. No frameworks, no build step.
- Backend: PHP with FlightPHP, PDO for all database access, MySQL, JWT for auth.
- API docs: OpenAPI (Swagger UI). External API: NHTSA (VIN decoder, recalls).

## Architecture rules
- Never mix backend and frontend code. Backend is pure PHP returning JSON. Frontend is HTML/CSS/JS only.
- Layering: route -> service -> DAO -> database. Routes never call DAOs. Only services call DAOs.
- All SQL uses PDO prepared statements. No string-concatenated queries.
- Shared DB logic lives in a base DAO class; each entity has its own DAO and service.
- Auth, role check, request logging and error handling are reusable middleware registered in FlightPHP, not copied into routes.
- Frontend (from M5): only files in `frontend/js/services/` talk to the backend; views never call `fetch` directly.
- Secrets (DB credentials, JWT secret, API keys) live in `backend/config/config.php`, which is git-ignored. Commit only `config.example.php`.
- Error responses never expose stack traces, SQL or file paths.

## Folder structure
```
backend/
  routes/       FlightPHP route files, one per entity
  services/     business logic and validation
  dao/          BaseDao + one DAO per entity
  middleware/   auth, role, request log, error handler
  config/       config.example.php (config.php is ignored)
  logs/         info.log, error.log (ignored)
frontend/
  index.html    app shell
  css/          style.css (theme tokens at top)
  js/           app.js router, later js/services/
  views/        HTML templates loaded into #app
  assets/       images
docs/           ERD.md, AI_LOG.md
```

## Naming conventions
- DB tables: plural snake_case (`service_records`). Columns: snake_case.
- PHP classes: PascalCase (`VehicleDao`, `VehicleService`). Files match the class name.
- JS: camelCase functions and variables. CSS classes for the app use the `ac-` prefix.
- Views: kebab-case file names matching the route (`vehicle-details.html`).
- Routes: REST style, plural nouns (`GET /vehicles`, `POST /vehicles`, `PUT /vehicles/{id}`).

## Design system
Classic black and white with red accents (sports-car feel). Tokens are CSS variables at the top of `frontend/css/style.css` (`--ac-red`, `--ac-black`, ...). Fonts: Barlow Condensed (headings), Barlow (body). Currency: KM. Do not introduce new colors or fonts without asking.

## Git workflow
One branch per milestone (`milestone1` ... `milestone5`). Open a PR to main with the course reviewer added. Never merge a milestone branch into main yourself.

## AI rules
- Update `docs/AI_LOG.md` every milestone, including real mistakes the AI made.
- Do not add libraries or features that were not asked for.
- Ask before changing the database schema.
