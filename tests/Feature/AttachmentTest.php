<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Core\Models\Attachment;
use Modules\Core\Models\Company;
use Modules\Finance\Models\Journal;

beforeEach(function () {
    Storage::fake('local');
    $this->company = Company::factory()->create();
    $this->journal = Journal::factory()->create(['company_id' => $this->company->id, 'status' => Journal::STATUS_DRAFT]);
    $this->clerk = companyUser(['finance.journals.view', 'finance.journals.create'], $this->company);
});

function attach(Journal $journal, $user, $company, ?UploadedFile $file = null)
{
    return actingInCompany($user, $company)->post(route('finance.journals.attachments.store', $journal->id), [
        'file' => $file ?? UploadedFile::fake()->create('rent-invoice.pdf', 120, 'application/pdf'),
        'description' => 'Landlord invoice',
    ]);
}

test('a file is attached privately, listed on the document and downloaded through the application', function () {
    attach($this->journal, $this->clerk, $this->company)->assertRedirect()->assertSessionHasNoErrors();

    $attachment = Attachment::withoutGlobalScopes()->sole();
    Storage::disk('local')->assertExists($attachment->file_path);
    expect($attachment->file_path)->toStartWith("attachments/{$this->company->id}/journals/");

    actingInCompany($this->clerk, $this->company)->get(route('finance.journals.show', $this->journal->id))->assertOk()->assertSee('rent-invoice.pdf')->assertSee('Landlord invoice');
    actingInCompany($this->clerk, $this->company)->get(route('finance.journals.attachments.download', [$this->journal->id, $attachment->id]))
        ->assertOk()
        ->assertDownload('rent-invoice.pdf');
});

test('only the uploader can remove a file, and only while the document is a draft', function () {
    attach($this->journal, $this->clerk, $this->company);
    $attachment = Attachment::withoutGlobalScopes()->sole();
    $colleague = companyUser(['finance.journals.view', 'finance.journals.create'], $this->company);

    actingInCompany($colleague, $this->company)->delete(route('finance.journals.attachments.destroy', [$this->journal->id, $attachment->id]))->assertSessionHas('error');
    $this->journal->update(['status' => Journal::STATUS_POSTED]);
    actingInCompany($this->clerk, $this->company)->delete(route('finance.journals.attachments.destroy', [$this->journal->id, $attachment->id]))->assertSessionHas('error');
    expect(Attachment::withoutGlobalScopes()->count())->toBe(1);

    $this->journal->update(['status' => Journal::STATUS_DRAFT]);
    actingInCompany($this->clerk, $this->company)->delete(route('finance.journals.attachments.destroy', [$this->journal->id, $attachment->id]))->assertSessionHas('success');
    expect(Attachment::withoutGlobalScopes()->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->file_path);
});

test('attaching needs the create permission and an allowed file type', function () {
    attach($this->journal, companyUser(['finance.journals.view'], $this->company), $this->company)->assertForbidden();
    attach($this->journal, $this->clerk, $this->company, UploadedFile::fake()->create('script.php', 1, 'application/x-php'))->assertSessionHasErrors('file');

    expect(Attachment::withoutGlobalScopes()->count())->toBe(0);
});

test('another company\'s files cannot be reached', function () {
    attach($this->journal, $this->clerk, $this->company);
    $otherCompany = Company::factory()->create();
    $outsider = companyUser(['finance.journals.view'], $otherCompany);

    actingInCompany($outsider, $otherCompany)
        ->get(route('finance.journals.attachments.download', [$this->journal->id, Attachment::withoutGlobalScopes()->sole()->id]))
        ->assertNotFound();
});
