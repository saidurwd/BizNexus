---
paths:
  - 'Modules/**/Controllers/**'
---

# Controllers

## Use 0/1, not booleans, in Rule::exists/unique conditions
Rule::exists()/unique()->where('flag', false) is serialised into the rule string, so false becomes '' (and true becomes '1'). MySQL coerces '' to 0 and hides the bug; SQLite (tests) matches nothing. Write ->where('is_group', 0) / ->where('is_postable', 1). Also compare date columns with whereDate(): the 'date' cast stores 'Y-m-d 00:00:00' on SQLite, so plain string comparisons fail on boundary dates.
