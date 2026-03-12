# SQL migrations

Run migrations manually in phpMyAdmin (or mysql client) in ascending order.

Naming format:

- `YYYYMMDD_HHMM_description.sql`

Rules:

- One logical change per file.
- Never edit an applied migration; add a new file for follow-up changes.
- Keep destructive operations explicit with comments.

