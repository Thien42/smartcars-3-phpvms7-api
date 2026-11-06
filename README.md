<h1 align="center">smartCARS 3 phpVMS 7 API</h1>
<div align="center">
    <i>Web Script Files for smartCARS 3</i>
</div>

## Introduction
smartCARS 3 is a web-based flight tracking system for virtual airlines. It is a complete rewrite of the original smartCARS system, and is designed to be more flexible and easier to use. This repository contains the web script files for smartCARS 3 in phpVMS 7.

## Requirements
- phpVMS 7.0.0 or Later (version 1.2.0 is tested on phpVMS 7.0.10)
- PHP 8.0 or higher
- A webserver that accepts the Authorization header (Apache, nginx, etc.)
    - If you are using a shared hosting provider, you may need to contact them to enable this feature.

## Installation
The smartCARS 3 phpVMS 7 API is a drag and drop module. It does not require any configuration. We support phpVMS 7 in this repository.

For phpVMS 5 support, please go to the [phpVMS 5 repository](https://github.com/invernyx/smartcars-3-phpvms5-api) instead.

### Step 1
Download the latest release from the [releases page](https://github.com/invernyx/smartcars-3-phpvms7-api/releases).

### Step 2
Move this folder into your `modules` folder, and verify that the folder is named `SmartCARS3phpVMS7Api`.

### Step 3
Using the Admin Interface, go to Modules and Enable the module.

### Step 4
Verify that the installation was successful by visiting the base URL in your browser. You should see a JSON response with the version number of the API and the name of your handler.

Assuming you have placed phpVMS 7 in your `public_html`:
`https://yourdomainhere.com/api/smartcars/`

This URL will be the "Script URL" option in smartCARS 3 Central when managing your community.

## Limitations

* Schedule Search Only Supports Departure and Arrival Airport
* v7 Flight Type codes are simplified to v5 flight type codes in the UI. This will not affect flight logging.
* The following phpVMS settings are ignored
  * Bids
    * Disable Flight On Bid
  * PIREPs
    * Restrict the flights to company

## Settings

The module adds a `smartcars` group to the phpVMS admin settings (run the module's migration after an update). Each setting defaults to the module's original behavior; a missing setting behaves like its default.

* **Airline code** (`smartcars.airline_code`): the airline code smartCARS shows for bookings and schedules. `iata` (default): the IATA code, or the ICAO code when the airline has none. `icao`: the ICAO code. PIREP lists always use the ICAO code.
* **Aircraft name** (`smartcars.aircraft_name`): `detailed` (default): `name (registration) | status`. `plain`: the aircraft name only.
* **Cancelled flights** (`smartcars.cancel_mode`): `cancel` (default): the PIREP stays, marked cancelled. `delete`: the unfiled PIREP is deleted with its ACARS positions, logs and flight analyses, and a SimBrief OFP stays attached to the booked flight so the pilot can fly it again.

## phpVMS Socialite SSO Login (Discord/VATSIM)

If a user creates their account on phpVMS 7 via Discord or VATSIM SSO, they do not have a typical email/password combination to properly login via smartCARS.

For these accounts, the user is to use a `email/api key` (where the user will enter their email as the username, and their api key as the password) combination. The API key for the logged in user can be accessed by viewing their profile on phpVMS 7.

## Charter Flights

Unlike phpVMS 5, Charter Flights behave differently in phpVMS 7.

For the callsign field, if just a number is supplied, your flight will be assigned automatically to the first airline in your system, or a airline ID you define in your .env file by using the `SC3_CHARTER_AIRLINE_ID` variable (e.g. `SC3_CHARTER_AIRLINE_ID=1` for airline id 1).

If you supply a ICAO or IATA code with the flight number (e.g. `DAL1421` or `DL1421`, the API will search to see if that code exists in the system. If it finds it, the flight will be flown under that code and flight number. If it cannot find it, it'll fallback to the first airline in your system or what's set in the env variable.

Only numeric flight numbers are supported. For example, `BAW47C` will turn into `BAW0` when you see the bid.

If your community has the Bids > Restrict Aircraft setting enabled, the aircraft selected will be attached to the bid. However, if the setting is not enabled, the subfleet for the same aircraft family will be attached to the flight for manual selection before beginning the flight.

## Pirep Distance Recalculation

Included with this API is a job that'll recalculate PIREP distances for all smartCARS pireps, based on the ACARS telemetry logs on the server.

To execute this, navigate directly to `/admin/smartcars/recalc` in your web browser (e.g. `https://myva.com/admin/smartcars/recalc`) to start the recalculation job.

If you have a private discord notification channel setup, you will receive progress updates regarding the status of the job as it executes on the backend.

## Flight analyses

smartCARS can upload the analyses of a finished flight (landing analysis, flight score, takeoff analysis, approach and takeoff trails, debrief). The module stores them in the `sc_flight_analyses` table, one row per PIREP, and answers `POST /pireps/analyses` (upload) and `POST /pireps/analyses/search` (lookup by PIREP IDs). Cancelling a flight deletes its row when the cancelled flights setting is `delete`.

Columns:

- `pirep_id`: the PIREP the row belongs to (unique)
- `user_id`: the pilot who uploaded it
- `format_version`: version of the payload format: 1 from smartCARS 3, 2 from clients with a `smartCARS/` User-Agent of major version 4 or later
- `touchdown_rate_fpm`: touchdown rate in feet per minute, for reports and queries
- `landing_grade`: `excellent`, `good`, `fair` or `poor`
- `flight_score`: blended flight score out of 100
- `landing_analysis`, `flight_score_detail`, `takeoff_analysis`, `debrief`, `approach_trail`, `takeoff_trail`: the full smartCARS payloads as JSON text, each can be null. They are stored exactly as sent: the module reads them from the raw request body, so phpVMS's `TrimStrings` / `ConvertEmptyStringsToNull` middleware does not alter them and `{}` stays an object

## Capabilities

`GET /` also answers `capabilities`, the optional features this backend supports (today `flight-analyses`, `arrival-search`, `iata-flight-types` (clients sending a `smartCARS/<major>` User-Agent with major 4 or later get the phpVMS flight type letter as stored in the flight `type`; other clients get P or C), while the `pireps.handle_diversion` setting is enabled `divert`, and while auto-update is enabled `auto-update`). Each endpoint behind one also names it in the `X-SmartCARS-Capability` response header. Both come from the `scCapability` default on the route, so a new capability is declared once, on its route.

## Auto-update

The module can update itself from GitHub whenever the fork's `master` branch is pushed. The updater is off by default and does nothing until it is switched on.

Settings (phpVMS Settings page, smartCARS group):

- `Auto-update`: turns the updater on. While it is off, the webhook answers without doing anything and nothing is downloaded, written or cleared.
- `Auto-update webhook secret`: the secret set on the GitHub webhook. A request without a matching `X-Hub-Signature-256` signature is rejected with 401, and an empty secret rejects every request.
- `Auto-update installed commit` and `Auto-update last result`: written by the updater (the commit installed by the last successful update, and the time, commit and outcome of the last attempt).

GitHub webhook (repository Settings > Webhooks): payload URL `<your site>/api/smartcars/auto-update`, content type `application/json` (not the form-encoded default: the module ignores a form-encoded push), the same secret, "Just the push event".

On a push to `master` the webhook only queues a check: the update job asks GitHub for the current head of `master` (`api.github.com`) and downloads that commit itself from `codeload.github.com/Thien42/smartcars-3-phpvms7-api`, never anything named by the request, so a replayed or redelivered webhook cannot install an older commit. The job:

1. skips a head that is already installed, and runs one update at a time (a lock file under `storage/app/smartcars-auto-update/`); a push arriving during an update queues one more run after it;
2. refuses when the web server user cannot write the module folder or `modules/`;
3. unpacks the archive under `storage/app/smartcars-auto-update/`, checks it is this module, checks every PHP file parses, and writes the commit into `Support/InstalledCommit.php`;
4. moves the current module to a backup there (never under `modules/`, where phpVMS would load it as a second module) and moves the new one in;
5. runs `module:migrate SmartCARS3phpVMS7Api --force`, then clears the application, route, config and view caches and OPcache;
6. requests `GET /api/smartcars` under the site's `APP_URL`; unless it answers 200 with `handler` `phpvms7` and the new `commit` (read from `Support/InstalledCommit.php`, so cached old code reports the old commit), it puts the backup back and clears the caches again.

Requirements and limits:

- The phpVMS cron must run (it runs queued jobs every minute, with `queue:work`'s 60 s timeout; the job keeps its network calls well under it). With the default `sync` queue, the update runs right after the webhook's response, inside that PHP-FPM worker, and the health check needs a second free worker (`pm.max_children` of at least 2); PHP-FPM's `request_terminate_timeout` must allow about a minute.
- `APP_URL` must be reachable from the server itself, or every update rolls back with the reason in the last result.
- Migrations only add tables, columns or settings: a rollback restores the old code but never runs a migration's `down`.
- With `opcache.validate_timestamps=0`, the web server keeps serving the old code until PHP-FPM restarts; the health check then sees the old commit, rolls back and says so.
- `GET /` lists `auto-update` through phpVMS's cached `setting()`, so the listing can lag the switch until the settings cache expires; the webhook and the job read the switch uncached.
- The module cannot depend on Composer packages beyond what phpVMS ships, since the updater cannot run Composer.
- The first install of a version with the updater is a manual copy, like any other module update.
