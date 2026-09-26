<?php

use Illuminate\Support\Facades\File;
use Modules\Core\Models\Company;

/**
 * Every string the application passes to __() or trans_choice(), read from views and PHP code.
 *
 * @return list<string>
 */
function translatableStrings(): array
{
    $files = collect(File::allFiles(resource_path('views')))
        ->merge(File::allFiles(base_path('Modules')))
        ->merge(File::allFiles(app_path()))
        ->filter(fn ($file) => str_ends_with($file->getFilename(), '.php') && ! str_contains($file->getPathname(), '/Migrations/') && ! str_contains($file->getPathname(), '/views/vendor/'));

    return $files->flatMap(function ($file) {
        preg_match_all('/(?:__|trans_choice)\(\s*(?:\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\]|\\\\.)*)")/', $file->getContents(), $matches, PREG_SET_ORDER);

        return collect($matches)->map(fn (array $match) => isset($match[2]) && $match[2] !== '' ? stripslashes($match[2]) : str_replace(["\\'", '\\\\'], ["'", '\\'], $match[1]));
    })
        ->reject(fn (string $key) => $key === '' || str_contains($key, '$') || preg_match('/^[a-z_]+(::)?[a-z_]*\.[a-z_.]+$/', $key))
        ->unique()
        ->values()
        ->all();
}

test('every translated language has all of the application\'s strings', function () {
    $locales = collect(array_keys(config('app.supported_locales')))->reject(fn (string $locale) => $locale === 'en');
    $strings = translatableStrings();

    foreach ($locales as $locale) {
        $path = lang_path("erp/{$locale}.json");
        expect(File::exists($path))->toBeTrue("Missing lang/erp/{$locale}.json");

        $missing = array_values(array_diff($strings, array_keys(json_decode(File::get($path), true))));
        expect($missing)->toBe([], "Untranslated strings in lang/erp/{$locale}.json");
    }
});

test('translations keep their placeholders and plural forms', function () {
    foreach (File::glob(lang_path('erp/*.json')) as $path) {
        foreach (json_decode(File::get($path), true) as $key => $translation) {
            preg_match_all('/:[a-z_]+/', $key, $expected);
            preg_match_all('/:[a-z_]+/', $translation, $actual);
            $expectedPlaceholders = $expected[0];
            $actualPlaceholders = $actual[0];
            sort($expectedPlaceholders);
            sort($actualPlaceholders);

            expect($actualPlaceholders)->toBe($expectedPlaceholders, basename($path)." changes the placeholders of \"{$key}\"")
                ->and(substr_count($translation, '|'))->toBe(substr_count($key, '|'), basename($path)." changes the plural forms of \"{$key}\"");
        }
    }
});

test('a user working in Arabic gets a right-to-left page in Arabic, including the menu', function () {
    $company = Company::factory()->create();
    $user = companyUser(['finance.dashboard.view'], $company);
    $user->update(['locale' => 'ar']);

    actingInCompany($user, $company)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('dir="rtl"', false)
        ->assertSee('بحاجة إلى متابعة')
        ->assertSee('الرئيسية')
        ->assertDontSee('Needs attention');
});
