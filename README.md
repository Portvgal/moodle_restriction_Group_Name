# Moodle Availability Restriction by Group Name

`availability_groupname` is a Moodle availability condition plugin that restricts access based on the current user's course group names.

It adds a **Group name** restriction type to Moodle's standard availability condition editor. The condition passes when the user belongs to at least one course group whose name matches the configured text value.

## Requirements

- Moodle 4.5 LTS or later
- Tested with Moodle 4.5 and Moodle 5.2

## Installation

Install this plugin into the Moodle source tree at:

```text
availability/condition/groupname
```

Then run the Moodle upgrade:

```bash
php admin/cli/upgrade.php
```

Purge caches after installation if needed:

```bash
php admin/cli/purge_caches.php
```

## Usage

1. Edit an activity or course section.
2. Open **Restrict access**.
3. Select **Add restriction...**.
4. Choose **Group name**.
5. Select a match mode:
   - **is equal to**
   - **contains**
   - **starts with**
6. Enter one group name value, for example `25_32`.

Values are treated as plain text. Numbers, underscores, and other characters have no special meaning.

Matching is case-insensitive and the configured value is trimmed before it is saved.

## Multiple Values

This plugin intentionally stores one group-name rule per availability condition. For OR logic, use Moodle's standard restriction sets.

Example:

```text
Student must match any of the following:
- Group name starts with 25_32
- Group name starts with 26_32
- Group name starts with 26_77
```

This keeps each condition simple and makes reporting against Moodle's availability JSON easier.

## Stored Availability JSON

Conditions are stored in Moodle's existing availability JSON fields. No database tables are added.

Example:

```json
{
  "type": "groupname",
  "op": "startswith",
  "v": "25_32"
}
```

Supported operators:

- `exact`
- `contains`
- `startswith`

## Behaviour

- Blank values are rejected.
- Matching is case-insensitive.
- Users pass if any of their course group names match the configured value.
- Moodle core handles `must` / `must not` through the standard availability negation flag.
- Moodle core handles `match all` / `match any` through restriction sets.
- Moodle core handles visible / hidden eye behaviour.
- Users with `moodle/site:accessallgroups` pass the restriction, matching Moodle's core group availability condition behaviour.

## Testing

Automated tests are included in:

```text
tests/condition_test.php
```

The plugin has been tested with:

- Moodle 4.5.11+
- Moodle 5.2.1+

Test coverage includes:

- Exact matching with different letter case
- Contains matching
- Starts-with matching
- Numeric and underscore values as plain text
- Multiple group membership
- Users with no matching groups
- Blank value rejection
- Invalid operator rejection
- Negated conditions
- `moodle/site:accessallgroups`
- Equivalent user filtering via PHP and SQL
- Stable saved JSON

Run the plugin tests from the Moodle root:

```bash
vendor/bin/phpunit availability/condition/groupname/tests/condition_test.php
```

For Moodle 5.2 public-directory layouts, run:

```bash
vendor/bin/phpunit public/availability/condition/groupname/tests/condition_test.php
```

## License

GPL v3 or later. See `LICENSE`.
