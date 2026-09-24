<?php

test('profile page is displayed', function () {
    $user = companyUser();

    $response = $this
        ->actingAs($user)
        ->withSession(['active_company_id' => $user->userCompanies()->value('company_id')])
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = companyUser();

    $response = $this
        ->actingAs($user)
        ->withSession(['active_company_id' => $user->userCompanies()->value('company_id')])
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = companyUser();

    $response = $this
        ->actingAs($user)
        ->withSession(['active_company_id' => $user->userCompanies()->value('company_id')])
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = companyUser();

    $response = $this
        ->actingAs($user)
        ->withSession(['active_company_id' => $user->userCompanies()->value('company_id')])
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = companyUser();

    $response = $this
        ->actingAs($user)
        ->withSession(['active_company_id' => $user->userCompanies()->value('company_id')])
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});
