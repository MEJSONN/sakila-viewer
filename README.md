# Sakila Viewer — Film Rental Demo

A simple, lightweight PHP web app for browsing and managing a film rental catalog based on the Sakila sample database.

This repository contains a small demo UI for viewing films, filtering by title and category, and performing basic admin actions (add / edit / delete) and simple rental operations.

## Features

- Browse catalog with title search and category filters
- Pagination of film list
- Film detail pages with length, language and actor information
- Simple rental flow that marks an available inventory copy as rented
- Admin panel for adding, editing and deleting films
- Add/remove inventory copies when editing a film
- Theme toggle (light / dark) persisted in localStorage

## Quick facts / contract

- Input: HTTP requests via browser; forms use GET/POST
- Output: HTML pages rendered by PHP
- Success criteria: able to view films and perform admin and rental actions against a local Sakila database
- Error modes: basic errors are printed to page; there is no authentication or advanced error handling

## Requirements

- Windows / macOS / Linux
- XAMPP / PHP (7.0+) with MySQL/MariaDB or equivalent
- Browser

## Setup (development / local)

1. Copy the project to your web server document root, e.g. with XAMPP put it at:

   c:\xampp\htdocs\sakila-viewer

2. Start Apache and MySQL (via XAMPP control panel).

3. Import the Sakila schema and data using phpMyAdmin or MySQL CLI. In phpMyAdmin you can import the SQL files located in the `db-files/` folder:

   - `db-files/sakila-schema.sql`
   - `db-files/sakila-data.sql`

   Alternatively with the mysql client (adjust user/host/paths as needed):

   mysql -u root -p < db-files/sakila-schema.sql
   mysql -u root -p < db-files/sakila-data.sql

   The project expects a database named `sakila`.

4. By default the app connects with these credentials in the PHP files:

   - host: `localhost`
   - user: `root`
   - password: `` (empty)
   - database: `sakila`

   If your MySQL user or password differs, update the connection in each PHP file (e.g., `index.php`, `admin.php`, `film.php`, etc.) or centralize the config for your work.

5. Open the app in your browser:

   http://localhost/sakila-viewer/index.php

## Usage

- Browse films on `index.php`.
- Click a film title or "Szczegóły" to open `film.php?fid=...` for details.
- Use the admin panel at `admin.php` to add, edit or delete films.
- The add/edit screens let you select actors, language, category and manage copy counts.
- Rent a film with `rent.php?fid=...` (the demo uses a default `customer_id=1` and `staff_id=1`).

## File overview

- `index.php` — public film catalog with search, category filters and pagination
- `admin.php` — admin list (add / edit / delete) with same filtering UI
- `film.php` — detailed view for a single film
- `add.php` — form to add a new film (adds film, categories, actors, inventory rows)
- `edit.php` — edit film data, category, actors and inventory copies
- `delete.php` — delete a film and related inventory/rental records
- `rent.php` — simple rental flow that inserts a `rental` row for an available inventory copy
- `styl.css` — app styles and dark theme variables
- `db-files/` — SQL files (`sakila-schema.sql`, `sakila-data.sql`, `.mwb`) used to create/import the Sakila DB

## Notes, security & limitations

This project is a demo/learning app and is NOT production ready. Key limitations and suggested improvements:

- No authentication/authorization — admin pages are unprotected. Add login and role checks before using in real environment.
- Mixed use of prepared statements and direct variable interpolation — some queries (especially ones interpolating `$fid`) are vulnerable to SQL injection in their current form. Use prepared statements consistently.
- No CSRF protection on forms — add tokens for state-changing POST actions.
- Minimal error handling and user feedback — consider improving exception handling and user-friendly messages.
- Hard-coded `customer_id` and `staff_id` in rental flow — implement customer selection and real staff handling.
- If deploying to public servers, do NOT use the `root` DB user or an empty password.

## Development notes / suggestions

- Centralize DB connection in a single include (e.g., `config.php`) to avoid repeating credentials across files.
- Use prepared statements for all SQL with user-supplied values.
- Sanitize and validate input on both client and server sides.
- Add simple unit/integration tests (e.g., PHPUnit) for DB interactions when refactoring.

## Troubleshooting

- Blank page / errors: check Apache/PHP error logs and enable display_errors for development.
- Import failures: MySQL versions differ; the Sakila schema is a common sample but ensure your server supports the features used.

## Contributing

Small fixes and improvements are welcome. Suggested PRs:

- Centralize DB config
- Full prepared-statement migration
- Add authentication for the admin area
