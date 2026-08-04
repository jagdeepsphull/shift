# PickAShift — CodeIgniter 3 → CodeIgniter 4 migration

The application was upgraded from **CodeIgniter 3.1.13** to **CodeIgniter 4.7.4**
in place. The same MySQL database (`pickashift`) is used — no schema change was
made and no data was migrated.

## Where the old application is

Everything that was in this folder before the upgrade is in:

```
_backup_ci3_20260804/
├── application/            complete CI3 application
├── system/                 CI3 framework
├── assets/  uploads/       copies of the asset folders
├── index.php .htaccess     old front controller / rewrite rules
├── _root_old_files/        the loose root files (old zips, info.php, cronjob1.php, …)
└── pickashift_db_20260804.sql   mysqldump taken before the upgrade
```

To roll back: delete the new folders/files listed below and copy the contents of
`_backup_ci3_20260804/` back into the project root.

## New layout

The project keeps the *document root* as the web root (the "shared hosting"
CodeIgniter 4 layout) so that **every public URL is unchanged**:

```
index.php            CI4 front controller (was public/index.php)
.htaccess            rewrite rules + denies /app /vendor /writable /tests /.env …
.env                 environment configuration (NOT for the live server)
app/                 the application
  Commands/          jobs:expire (the old cronjob1.php)
  Config/            App, Database, Routes, AppSettings, Constants, Events …
  Controllers/       Front, Applicant, Employer, Sadmin, Welcome, Cron
  Helpers/           common_helper, ci3compat_helper, captcha_helper
  Language/          english/content.php, hindi/content.php
  Libraries/         compat shims + Iptracker
  Models/            CustomModel, UsersapiModel
  Views/             all the CI3 views
assets/ uploads/     unchanged, still served directly
vendor/              CodeIgniter 4 + dependencies (composer)
writable/            cache, logs, sessions, uploads scratch
```

## How the CI3 code was carried over

CodeIgniter 4 has no `$this->load`, `$this->input`, `$this->uri`,
`$this->form_validation` or `$this->config`. Rather than rewrite ~5 000 lines of
controller logic, `App\Controllers\BaseController` exposes those names again,
backed by CI4 services:

| CI3                              | Provided by                                   |
| -------------------------------- | --------------------------------------------- |
| `$this->custom`                  | `App\Models\CustomModel`                      |
| `$this->db`                      | `db_connect()`                                |
| `$this->session->userdata()` …   | `App\Libraries\SessionCompat`                 |
| `$this->input->post()` …         | `App\Libraries\InputCompat`                   |
| `$this->uri->segment()`          | `App\Libraries\UriCompat` / `uri_segment()`   |
| `$this->config->item()`          | `App\Libraries\ConfigCompat` / `config_item()`|
| `$this->load->front_view()` …    | `App\Libraries\LoaderCompat`                  |
| `$this->form_validation`         | `App\Libraries\FormValidationCompat`          |
| `get_instance()`                 | `app/Helpers/ci3compat_helper.php`            |
| `redirect($uri)` (halts)         | `ci_redirect($uri)`                           |

Notes:

* **`ci_redirect()`** replaces CI3's `redirect()`. CI4's own `redirect()` returns
  a response object the controller must return; the ported code calls it in the
  middle of a method and relies on execution stopping, so `ci_redirect()` sends
  the header and exits. `ci_redirect($uri, 'refresh')` sends a `Refresh:` header,
  exactly as CI3 did (so the browser navigates but the HTTP status stays 200).
* **`validation_errors()` and `set_value()`** in `ci3compat_helper.php`
  deliberately shadow CI4's form-helper functions, because the views echo the
  result as a string. `ci3compat` is therefore listed **first** in
  `Config\Autoload::$helpers`.
* **Routing** uses CI4's legacy auto-router (`Config\Routing::$autoRoute = true`,
  `Config\Feature::$autoRoutesImproved = false`), which maps
  `Controller/method/segment/segment` exactly like CI3. The four custom CI3
  routes (`resources`, `contact`, `terms`, `policy`) are in `app/Config/Routes.php`.
* **Error level.** The ported views read many optional variables directly
  (`$pageTitle`, `$u_fname`, …). CI3 printed nothing for those; CI4 turns the PHP
  warning into an exception. `app/Config/Boot/*.php` therefore leaves warnings and
  notices to PHP's own handler. Exceptions, errors and fatals still surface
  normally.
* **The CI3 `LanguageLoader` hook** and the auto-loaded `Iptracker` library are
  now `post_controller_constructor` listeners in `app/Config/Events.php`.
* **Views** were copied unchanged except for the CI3 API calls:
  `$this->session->flashdata()` → `session()->getFlashdata()`,
  `$this->custom->…` → `custom()->…`, `$this->lang->line('x')` → `lang('content.x')`,
  `$this->uri->segment()` → `uri_segment()`, and three direct `$this->db` query
  builder blocks rewritten to the CI4 builder API.

## Configuration

`app/Config/Database.php` holds the live credentials (as CI3 did);
`.env` overrides them locally. `app/Config/App.php` has the live `baseURL`;
`.env` points it at `http://localhost/pickashift/`.

**Deploying:** upload everything except `.env`, `_backup_ci3_20260804/` and
`tests/`, then either create a `.env` on the server with `CI_ENVIRONMENT = production`
and the live database credentials, or rely on the defaults already in
`app/Config/`. Make sure `writable/` is writable by the web server.

## Cron

The stand-alone `cronjob1.php` (which opened its own mysqli connection) is now
`App\Controllers\Cron::expire_jobs`. All three of these work:

```
https://<site>/cronjob1.php      (routed, so an existing cron entry keeps working)
https://<site>/cron/expire_jobs
php spark jobs:expire            (preferred for a real cron job)
```

## Tests

`tests/e2e/` holds a Playwright suite covering the admin back office — login and
its failure modes, every list/add/edit screen, and the full CRUD lifecycle
through the UI. See `tests/e2e/README.md`.

```bash
cd tests/e2e && npm install && npm test
```

## Known pre-existing problems (present in CI3 too, left as they were)

| URL | Problem |
| --- | --- |
| `employer/applied_applicants/<id>` | loads view `employer/applied_applicants.php`, which does not exist (the file is called `applied_candidates.php`) |
| `sadmin/applied_applicants/<id>`   | loads view `admin/application/applied_applicants.php`, which does not exist |
| `applicant/saved_jobs`             | its SQL selects `agency.u_a_comp_name`, a column the `users` table does not have |

Dead CI3 controller methods with neither a view nor a database table
(`Sadmin::categories`, `Sadmin::roles`, `Sadmin::directory_listing`,
`Sadmin::foreign_employer`, `Sadmin::registration`, `Sadmin::ajax_getstates`,
`Sadmin::ajax_getcities`, `Front::update_password`) were not carried over. The
CI3 originals are in the backup folder if any of them is ever needed.
