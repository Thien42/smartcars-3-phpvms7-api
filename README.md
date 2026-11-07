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

The job recomputes the distance from the positions for every PIREP filed under the module's default source name (`pireps.source_name`) and replaces the stored distance, including one filed with `distanceFlown` (see "Optional flight data" below). PIREPs a client filed under a versioned source name are left alone.

If you have a private discord notification channel setup, you will receive progress updates regarding the status of the job as it executes on the backend.

## Optional flight data

A client may send optional fields on `flights/update` and `flights/complete` so the flight fills phpVMS's own fields (live map, PIREP page, admin views, finances) like a native ACARS client. Each field is used only when it is sent and plausible; a missing, null or implausible value keeps the behavior described for it. A client that sends none of them sees no change.

`flights/update`:

- `ias` (kt, > 0): the position's indicated airspeed (`acars.ias`). Without it the position has no IAS.
- `verticalSpeed` (fpm, any finite number, negative when descending): the position's vertical speed (`acars.vs`).
- `altitudeAgl` (ft, >= 0): the position's height above ground (`acars.altitude_agl`), with `altitude` as `altitude_msl`. Without it both take `altitude`.
- `distanceFlown` (nm, > 0): the position's distance (`acars.distance`) and the PIREP's distance (`pireps.distance`), stored in nautical miles, phpVMS's internal unit, whatever the VA's display unit. Without it the position's distance is the planned distance minus `distanceRemaining` and the PIREP's distance is left alone.
- `flightTime` (decimal hours, > 0): the PIREP's flight time (`pireps.flight_time`, stored in minutes). Without it the flight time is the time since the first position.
- `paused` (boolean): `true` sets the PIREP status to paused (`PSD`); the PIREP stays in progress, so it stays on the live map and can still be filed. phpVMS's hourly expiry crons skip paused flights, so a flight abandoned while paused is not deleted automatically (the pilot or an admin cancels it). `false` or missing maps `phase` as before.
- `transponder` (text, exactly 4 octal digits, e.g. `7000`): the position's squawk (`acars.transponder`, an integer column, so `0200` is stored as `200`).
- `autopilot` (boolean): the position's autopilot state (`acars.autopilot`, a text column: `1` engaged, `0` not).
- `fuelFlow` (lbs/h, >= 0): the total fuel flow of all engines (`acars.fuel_flow`), stored in lbs per hour, phpVMS's internal fuel unit, whatever the VA's display unit, rounded to 2 decimals.
- `simTime` (`{ zulu, local }`, each `YYYY-MM-DDTHH:MM:SS` without offset): `zulu` is the position's sim time (`acars.sim_time`, stored as `YYYY-MM-DD HH:MM:SS`, the form phpVMS's own ACARS API stores; phpVMS orders the positions of its `acars` position API by it). A value not in that form, or not a real date, is ignored. `local` is accepted and not stored.
- `mach`, `headingTrue`, `headingMagnetic` (degrees), `pitch` (degrees, positive nose up) and `bank` (degrees): accepted and not stored, phpVMS has no column for them. `heading` stays the stored heading.

`flights/complete`:

- `score` (0 to 100): `pireps.score`, rounded. phpVMS's PIREP page shows the score and landing rate only when both are non-zero.
- `cruiseLevel` (ft, > 0): `pireps.level`, rounded.
- `blockFuel` (lbs, > 0): `pireps.block_fuel`. Without it the block fuel comes from the "Pushing back with N lbs/kgs of fuel" log line, as before.
- `zfw` (lbs, > 0): `pireps.zfw`.
- `distanceFlown` (nm, > 0): `pireps.distance`. Without it the distance is computed from the positions.
- `blockTime` (decimal hours, > 0): accepted and not stored. phpVMS's `pireps` table has no block time column (its `Pirep` model names one that no migration creates); the block off and on times below carry it.
- `blockOffTime` and `blockOnTime` (ISO 8601 UTC, e.g. `2026-11-06T14:05:00Z`): `pireps.block_off_time` and `pireps.block_on_time`. Without them the server times recorded by the updates stay (phpVMS uses the filing time for a missing block on time). The filed times are ignored, and the stored ones kept, when they would put the off-block time after the on-block time, including when only one is sent and the other is a stored server time.
- `passengers` (integer, >= 0) and `cargo` (lbs, >= 0): the loads flown. They replace the PIREP's fares: the passengers are split over the aircraft's passenger fares and the cargo over its cargo fares, in proportion to each fare's capacity (phpVMS's subfleet and flight fare overrides), as whole counts that add up to the total. Cargo is converted to kg when the VA's weight unit is kg, as phpVMS's own SimBrief form counts cargo fares in that unit. A kind that is not sent, or that the aircraft has no fare for, keeps the fares set at start.
- `aircraft` (aircraft ID): when it differs from the PIREP's aircraft, the PIREP takes it only if it exists, its subfleet is one the pilot may fly (phpVMS's rank and type rating restrictions), it is parked, no other flight in progress uses it, and (with phpVMS's SimBrief or bid aircraft blocking enabled) no other pilot holds it. Otherwise the PIREP keeps its aircraft and the reason is logged. Fares are not regenerated for the new aircraft.
- `network` and `simVersion` (text): stored as the PIREP's `Network` and `Simulator` field values (source ACARS), shown on the PIREP page. The module's migration creates the two PIREP field definitions unless the VA already has fields with those slugs.

At filing the module also saves the planned route rows from the PIREP's route (phpVMS's `saveRoute`, as its own ACARS file endpoint does) when the PIREP has none, so the route is drawn on the PIREP page.

At start, when the pilot generated a SimBrief briefing in phpVMS for the flight with the started aircraft, the module saves the loads phpVMS rolled for that briefing (`simbrief.fare_data`) as the PIREP's fares. Otherwise it rolls the loads from the load factors, as before.

## Flight events

A client may send the flight log lines while the flight is in progress with `POST /flights/events`, so they show in the flight log on the PIREP page and in the admin before the flight is filed. The body is `{ uuid, bidID, events: [ { index, type, message, timestamp } ] }`:

- `uuid`: the PIREP ID of the ongoing flight (`bidID` is accepted and not used)
- `index` (integer, >= 0): the line's 0-based position in the flight log, the same number the line has as `eventId` in the `flightData` filed with `flights/complete`
- `type`: `info`, `event` or `comment`; checked, not stored
- `message` (text, may be empty): the line, stored cut to 255 characters (phpVMS's `acars.log` column). phpVMS's request middleware trims it and turns an empty or blank one into null, which is stored as null (no log text)
- `timestamp`: the line's UTC time, the `flightData` item's `eventTimestamp` (e.g. `2026-11-06 14:05:00z`), parsed as at filing

Each line may also carry these optional fields. A missing or malformed one is ignored and never refuses the request; the line is stored without it:

- `simTime` (`{ zulu, local }`, each `YYYY-MM-DDTHH:MM:SS` without offset): `zulu` is the row's sim time (`acars.sim_time`, stored as `YYYY-MM-DD HH:MM:SS`), checked as on `flights/update`. `local` is accepted and not stored.
- `position` (`{ latitude, longitude, altitude }`, degrees and feet MSL): where the aircraft was when the line was written, stored as the row's `lat`, `lon` and `altitude_msl` when all three are finite numbers and latitude and longitude are in range. Otherwise the row keeps phpVMS's default position (0, 0, 0). The row's `altitude_agl` is not filled.
- `timeIntoFlight` (minutes since the flight started) and `variables` (an object of tracking values keyed by variable name): accepted and not stored, phpVMS has no column for them.

Each line becomes a phpVMS ACARS row of type LOG for the PIREP, with the timestamp as its `created_at`. The row ID is derived from the PIREP ID and `index`, so the request is idempotent: a line sent again is ignored and the row stored first stays. At `flights/complete`, a `flightData` item whose `eventId` matches a stored line is not stored again; every other item is stored as before, so a client that never calls `flights/events` sees no change. The raw `flightData` copy in `sc_pirep_logs` is kept in full either way.

A request carries at most 100 lines. Answers: 200 when stored, 401 for an invalid session, 404 when the pilot has no such flight, 409 when the flight is no longer in progress or paused (filed or cancelled), and 422 when `events` is not an array, is empty or has more than 100 entries, or an entry lacks an integer `index` >= 0, a valid `type`, a `message` (text, empty allowed) or a parseable `timestamp`. A batch is checked as a whole: one invalid entry stores nothing. Expect a client to retry on any other status (a 500, for example): it sends the same lines again later.

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

`GET /` also answers `capabilities`, the optional features this backend supports (today `flight-analyses`, `flight-events`, `arrival-search`, `iata-flight-types` (clients sending a `smartCARS/<major>` User-Agent with major 4 or later get the phpVMS flight type letter as stored in the flight `type`; other clients get P or C), while the `pireps.handle_diversion` setting is enabled `divert`, and while auto-update is enabled `auto-update`). Each endpoint behind one also names it in the `X-SmartCARS-Capability` response header. Both come from the `scCapability` default on the route, so a new capability is declared once, on its route.

## Unchanged lists

`GET /data/airports` and `GET /data/aircraft` answer with an `ETag` header, the SHA-1 of the exact JSON body. A client may send it back as `If-None-Match`; an unchanged list then answers `304` with no body and the same `ETag`. The lists are still read from the database on every request, so the saving is the transfer and the client's own processing. `OPTIONS` answers are unchanged.

## Alternate airport

Every flight of `GET /flights/bookings`, `GET /flights/search` and `POST /flights/search/arrivals` carries `alternateAirport`, the alternate airport published on the flight, or `null` when it has none. `POST /flights/start` stores it as the alternate of the PIREP it creates.

## Flight search order and pages

`GET /flights/search` and `POST /flights/search/arrivals` return their flights in a stable order: by route distance, shortest first, then departure airport, then arrival airport, then flight ID. The route distance is the great-circle distance between the two airports' coordinates, the same for every flight of a route (not the flight's own `distance` field), so a route's flights are always adjacent. A flight whose airport has no coordinates (unset, or 0/0) comes last.

A page holds at most `limit` flights (250 at most, and 250 when `limit` is missing or below 1). The answer carries three headers:

- `X-SmartCARS-Total-Flights`: every flight matching the search, across all pages
- `X-SmartCARS-Total-Routes`: the number of distinct departure and arrival airport pairs among them
- `X-SmartCARS-Next-Cursor`: present when more results follow, absent on the last page

To get the next page, repeat the request unchanged with the cursor added as `cursor` (a query parameter on `flights/search`, a body field on `flights/search/arrivals`). The cursor is opaque and never expires: it holds the position of the last flight sent, so a flight added or hidden between pages is neither skipped nor repeated among the remaining results. A change to an airport's coordinates between two page requests can move that route's flights. A malformed cursor answers `400` with `{"message": "Invalid cursor"}`. The totals use the same filters as the page, including the pilot's current airport when only flights from the current airport are allowed.

The body is the same JSON array of flights as before, and the headers are sent to every client; a client that ignores them gets the first page. On `flights/search/arrivals`, `perArrivalLimit` turns paging off: the answer keeps at most that many flights per arrival airport, in the order above, carries none of the three headers and ignores `cursor`.

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
