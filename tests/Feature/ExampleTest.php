<?php

it('redirects the home page to the login screen', function () {
    $this->get('/')->assertRedirect('/login');
});
