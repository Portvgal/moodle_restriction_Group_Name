# Moodle Plugins Directory Publishing Checklist

## Registration values

- Plugin name: Availability restriction by group name
- Component: `availability_groupname`
- Plugin type: Availability restriction
- Install path: `availability/condition/groupname`
- Source control URL: `https://github.com/Portvgal/moodle_restriction_Group_Name`
- Tracker URL: `https://github.com/Portvgal/moodle_restriction_Group_Name/issues`
- Documentation URL: `https://github.com/Portvgal/moodle_restriction_Group_Name#readme`
- License: GPL v3 or later
- Maturity: Stable
- Supported Moodle versions: 4.5 to 5.2

## Release

- Release tag: `v1.0.0`
- Release notes: `CHANGES.md`
- Screenshots: `docs/screenshots/`

## Local validation evidence

- Moodle codechecker: passed with zero errors and zero warnings.
- PHP lint: passed for all plugin PHP files.
- Moodle 4.5 PHPUnit: passed, `OK (5 tests, 16 assertions)`.
- Moodle 5.2 PHPUnit: passed, `OK (5 tests, 16 assertions)`.
- Manual Moodle 4.5 UI test: passed.
- Manual Moodle 5.2 UI test: passed.

## Moodle registration page

Register at:

```text
https://moodle.org/plugins/registerplugin.php
```
