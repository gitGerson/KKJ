<?php

test('users can store a preferred locale in the session', function () {
    $this->from('/login')
        ->post(route('preferences.locale'), ['locale' => 'en'])
        ->assertRedirect('/login')
        ->assertSessionHas('locale', 'en')
        ->assertSessionHasNoErrors();
});

test('locale middleware applies the stored locale', function () {
    $this->withSession(['locale' => 'en'])
        ->get('/login')
        ->assertSuccessful()
        ->assertSeeText('Log in to your account')
        ->assertDontSeeText('Masuk ke akun Anda');
});

test('unsupported locales are rejected', function () {
    $this->from('/login')
        ->post(route('preferences.locale'), ['locale' => 'fr'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors('locale');
});
