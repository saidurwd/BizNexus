<?php

use Modules\Core\Models\Company;
use Modules\Core\Models\Permission;
use Modules\Core\Models\Role;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->admin = companyUser(['core.roles.create', 'core.roles.update', 'core.roles.delete', 'finance.journals.view'], $this->company);
    $this->journalsView = Permission::where('slug', 'finance.journals.view')->first();
    $this->journalsPost = Permission::firstOrCreate(['slug' => 'finance.journals.post'], ['name' => 'Post Journal']);
});

test('an administrator can create a role with permissions they hold', function () {
    actingInCompany($this->admin, $this->company)
        ->post(route('core.roles.store'), ['name' => 'Viewer', 'slug' => 'viewer', 'permissions' => [$this->journalsView->id]])
        ->assertRedirect(route('core.roles.index'));

    expect(Role::where('slug', 'viewer')->firstOrFail()->permissions()->pluck('slug')->all())->toBe(['finance.journals.view']);
});

test('an administrator cannot add a permission they do not hold to a role', function () {
    actingInCompany($this->admin, $this->company)
        ->post(route('core.roles.store'), ['name' => 'Poster', 'slug' => 'poster', 'permissions' => [$this->journalsPost->id]])
        ->assertSessionHasErrors('permissions.0');

    expect(Role::where('slug', 'poster')->exists())->toBeFalse();
});

test('an administrator cannot change or delete a role more powerful than their own', function () {
    $powerfulRole = Role::create(['name' => 'Controller', 'slug' => 'controller', 'status' => 'active']);
    $powerfulRole->permissions()->attach($this->journalsPost);

    actingInCompany($this->admin, $this->company)
        ->put(route('core.roles.update', $powerfulRole->id), ['name' => 'Controller', 'slug' => 'controller', 'permissions' => []])
        ->assertForbidden();

    actingInCompany($this->admin, $this->company)
        ->delete(route('core.roles.destroy', $powerfulRole->id))
        ->assertForbidden();

    expect($powerfulRole->permissions()->pluck('slug')->all())->toBe(['finance.journals.post']);
});
