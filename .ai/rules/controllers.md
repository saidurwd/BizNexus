---
paths:
  - 'Modules/**/Controllers/**'
---

# Controllers

## Use 0/1, not booleans, in Rule::exists/unique conditions
Rule::exists()/unique()->where('flag', false) is serialised into the rule string, so false becomes '' (and true becomes '1'). MySQL coerces '' to 0 and hides the bug; SQLite (tests) matches nothing. Write ->where('is_group', 0) / ->where('is_postable', 1). Also compare date columns with whereDate(): the 'date' cast stores 'Y-m-d 00:00:00' on SQLite, so plain string comparisons fail on boundary dates.

## Check raw SQL against MySQL/MariaDB, not just the SQLite tests
Tests run on SQLite, which accepts things MariaDB/MySQL reject: reserved words as column aliases (e.g. `AS delayed` fails on MariaDB), identifiers over 64 chars, and loose GROUP BY. In selectRaw/DB::raw use descriptive, non-reserved aliases (waiting_count, delayed_count) and open new raw-SQL screens once against the dev MySQL database before committing.
