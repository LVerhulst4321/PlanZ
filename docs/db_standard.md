# Database Naming Standards for PlanZ

These are recommended naming standards database objects for the PlanZ project. There will be a separate document for general coding standards.

Many database objects predate the standard and do not follow it. These may be refactored over time.

## Database Name

The database name is defined in the `db_env.php` file. As such it is completely up to the implementation to follow any naming convention on their server.

## Table Names

Table names should be all lower case, and only ASCII letters should be used. Numbers may be used probiding the name starts with a letter, though in most cases should be avoided.

Words should be separated by underscores.

Table names should always be singular. Plurals are discouraged because not all table names can easily form plurals, so it's better to use the singular for everything for consistency - e.g. `user_has_permission_role`.

Where practical, single word table names are preferable. E.g., `participant`, `session`, `track`, `room`. However, sometimes there is no obvious single word name, so multiple words may be used, but every effort should be made to make the purpose clear. E.g., `patch_log`, `room_set`, `participant_interest`.

Tables linking two other tables in a many-to-many relationship should contain the names of both tables. A word describing the relationship may optionally be added. E.g., `participant_on_session`, `room_has_room_set`, `participant_has_permission_role`, `session_needs_service`.

## Column Names

As with table names, column names should be lower case, with underscores separating words.

- **Primary key**. In most cases primary keys should use an auto-incrementing integer. In general the primary key should just be called `id`.
- **Composite key**. For link tables where there can be only one link entry between two other tables, it may be preferable to use a composite primary key combining the two keys of the tables being linked.
- **Foreign key**. Use the column name being linked to followed by `_id`.
- **Flags**. Flag columns generally indicate a true/false state. These are generally best created with a type of `tinyint`, and use 0 to indicate false and 1 to indicate true. Flag column names should be prefixed with `is_`. E.g., `is_deleted`, `is_scheduled`.
- **Dates**. Date columns should clearly indicate their purpose in the name. Do not ever call a date field `date`. Apart from being a reserved word, so every use would require quoting, it is not clear what the date is for. It's generally helpful for dates field names to end with `_date`.

**Note:** Do not _ever_ use a date field for a primary key, like my mate Andy used to insist on doing. It will cause no end of pain. Just don't.

## Foreign Keys

We mentioned foreign key columns above, but we also need to name foreign key constraints.

The name should clearly and uniquely describe the tables being linked. For example, the foreign key from `participant` to `participant_on_session` should be called `participant_participant_on_session`.

## Stored Procedures and Functions

**Stored procedures** should be named to clearly define the action they perform.

- Stored procedures should be prefixed with `sp_`.
- If a procedure acts primarily on a single table, it should include the table name in the procedure name, after `sp_`.
- The name should end with a description of the action being performed.
- Tables that act on multiple tables should have a clear description of the action.

Examples include, `sp_participant_update_display_name`, `sp_participant_on_session_add`.

**Functions** follow the same principals as stored procedures. Most perform a calcualtion on parameters and return a value. If a table is involved, try to reference in the name. I like to prefix functions with `f_`.


