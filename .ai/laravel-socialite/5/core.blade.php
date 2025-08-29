# Laravel Socialite v5 Specific Guidelines

## Version 5 Enhanced Features

### Improved Error Handling
```php
use Laravel\Socialite\Two\InvalidStateException;

try {
    $user = Socialite::driver('github')->user();
} catch (InvalidStateException $e) {
    Log::warning('Invalid OAuth state', ['error' => $e->getMessage()]);
    return redirect('/login')->with('error', 'Authentication session expired. Please try again.');
}
```

### Type Hints Best Practice
```php
use Laravel\Socialite\Contracts\User as SocialiteUser;

public function handleCallback(string $provider): RedirectResponse
{
    $socialiteUser = Socialite::driver($provider)->user();
    
    $user = $this->findOrCreateUser($socialiteUser, $provider);
    Auth::login($user);
    
    return redirect()->intended('/dashboard');
}

private function findOrCreateUser(SocialiteUser $socialiteUser, string $provider): User
{
    return User::updateOrCreate([
        'provider_name' => $provider,
        'provider_id' => $socialiteUser->getId(),
    ], [
        'name' => $socialiteUser->getName(),
        'email' => $socialiteUser->getEmail(),
        'avatar' => $socialiteUser->getAvatar(),
    ]);
}
```