---
paths:
  - '**/Migrations/**'
---

# Migrations

## Name long composite indexes explicitly (MySQL 64-char limit)
Laravel auto-names indexes as table_col1_col2_..._type. MySQL rejects identifiers over 64 chars, but the SQLite test DB does not, so tests pass while `migrate` fails in dev/prod. Pass an explicit short name to index()/unique()/foreign() whenever the generated name could exceed 64 chars. Also: MySQL DDL is not transactional, so a failed create migration leaves a partial table behind. Guard drops of indexes that may not exist with Schema::hasIndex().
