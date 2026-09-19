# Attendance schema

The attendance migrations target Laravel 9 and MySQL/MariaDB with InnoDB.
They follow the 2026-09-16 attendance design: employee profiles, schedules,
holidays, import batches, raw punches, daily/monthly summaries, requests,
approval steps, period locks and audit records.

`users.employee_id` is nullable and unique. Existing permissions are retained.
Employee codes are strings so leading zeroes survive imports. Raw punches are
unique by employee, timestamp and device; normalize missing device IDs to `''`.
The unique punch index also serves employee/time-range lookups. Daily and monthly
summaries have one row per employee/date and employee/year/month respectively.

Foreign keys restrict deletion of attendance history. Nullable account, manager,
department and import references use SET NULL. Use inactive/resigned statuses
instead of deleting employees. Audit model references are polymorphic and cannot
have a conventional foreign key.

Application services must validate date ordering, weekday/month ranges,
overlapping schedule assignments, leave sessions and approval transitions.
They must also enforce locked periods and recalculate affected summaries;
migrations do not implement these workflows.

## Initial installation on this checkout

At inspection, the configured local database `izi` was empty. The legacy
`2026_08_14_100000_add_course_tabs_fields_to_posts_table.php` migration depends on
a missing `posts` table and must not be run as part of attendance installation.
Use an explicit migration path list for the existing users and permission
migrations plus all `2026_09_16_*` migrations, with `--pretend` first. Do not use
`migrate:fresh`, `migrate:refresh`, or import the unrelated legacy schema dump.

The existing password resets, failed jobs, access tokens and course migrations
remain pending. A subsequent unscoped `php artisan migrate` will still require
the legacy `posts` schema to be resolved separately.

Rollback methods are for controlled rollback only: rolling back table-creation
migrations deletes those tables and their contents. Back up populated tables
before any future rollback.
