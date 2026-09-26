---
paths:
  - 'resources/views/**'
---

# Views

## Wrap UI text in __() and translate it in lang/erp/*.json
All user-facing text goes through __('English text') (placeholders as :name, plurals via trans_choice with |). The application's translations live in lang/erp/{locale}.json (registered with loadJsonTranslationsFrom, kept apart from laravel-lang's lang/{locale}.json and lang/{locale}/*.php, which lang:update may overwrite). Sidebar labels are translated in lang/{locale}/menu.php (AdminLTE looks up "menu.<label>"). tests/Feature/TranslationTest.php fails when a string used in code is missing from any supported locale or a translation drops a placeholder, so add every new string to all lang/erp/*.json files.
