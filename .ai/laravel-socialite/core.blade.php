# Laravel Socialite Guidelines

Laravel Socialite provides a simple, convenient way to authenticate with OAuth providers like Facebook, Twitter, Google, LinkedIn, GitHub, GitLab, and Bitbucket.

## Installation

```bash
composer require laravel/socialite
```

## Configuration

1. Add OAuth provider credentials to your `.env` file:

```env
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URL=http://your-app.com/auth/callback/google

GITHUB_CLIENT_ID=your-github-client-id
GITHUB_CLIENT_SECRET=your-github-client-secret
GITHUB_REDIRECT_URL=http://your-app.com/auth/callback/github
```

2. Add the provider configuration to `config/services.php`:

```php
'github' => [
    'client_id' => env('GITHUB_CLIENT_ID'),
    'client_secret' => env('GITHUB_CLIENT_SECRET'),
    'redirect' => env('GITHUB_REDIRECT_URL'),
],

'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URL'),
],
```

## Usage Patterns

### Basic Authentication Flow

1. **Redirect to Provider**:
```php
use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/redirect/{provider}', function (string $provider) {
    return Socialite::driver($provider)->redirect();
});
```

2. **Handle Provider Callback**:
```php
Route::get('/auth/callback/{provider}', function (string $provider) {
    $user = Socialite::driver($provider)->user();
    
    // Find or create user
    $authUser = User::updateOrCreate([
        'email' => $user->getEmail(),
    ], [
        'name' => $user->getName(),
        'provider_id' => $user->getId(),
        'provider_name' => $provider,
        'avatar' => $user->getAvatar(),
    ]);
    
    Auth::login($authUser);
    
    return redirect('/dashboard');
});
```

### User Data Access

```php
$user = Socialite::driver('github')->user();

// Basic info
$user->getId();
$user->getNickname();
$user->getName();
$user->getEmail();
$user->getAvatar();

// Provider-specific data
$user->user; // Raw user object from provider
$user->token; // OAuth token
$user->refreshToken; // OAuth refresh token (if available)
$user->expiresIn; // Token expiration time
```

### Stateless Authentication

For API usage, use stateless authentication:

```php
$user = Socialite::driver('github')->stateless()->user();
```

### Scopes and Parameters

Request specific scopes or parameters:

```php
return Socialite::driver('github')
    ->scopes(['read:user', 'public_repo'])
    ->with(['allow_signup' => 'false'])
    ->redirect();
```

## Best Practices

### Database Schema

Create a flexible user schema that supports multiple providers:

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password')->nullable(); // Nullable for social-only users
    $table->string('provider_name')->nullable();
    $table->string('provider_id')->nullable();
    $table->string('avatar')->nullable();
    $table->rememberToken();
    $table->timestamps();
    
    $table->unique(['provider_name', 'provider_id']);
});
```

### User Model

```php
class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider_name',
        'provider_id',
        'avatar',
    ];
    
    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    
    public function isSocialUser(): bool
    {
        return !is_null($this->provider_name);
    }
}
```

### Security Considerations

1. **Validate Provider**:
```php
Route::get('/auth/redirect/{provider}', function (string $provider) {
    if (!in_array($provider, ['github', 'google', 'twitter'])) {
        abort(404);
    }
    
    return Socialite::driver($provider)->redirect();
});
```

2. **Handle Existing Users**:
```php
Route::get('/auth/callback/{provider}', function (string $provider) {
    $socialUser = Socialite::driver($provider)->user();
    
    // Check if user exists with this email but different provider
    $existingUser = User::where('email', $socialUser->getEmail())->first();
    
    if ($existingUser && $existingUser->provider_name !== $provider) {
        return redirect('/login')->with('error', 'This email is already registered with a different provider.');
    }
    
    // Your user creation logic here
});
```

3. **Rate Limiting**:
```php
Route::get('/auth/redirect/{provider}', function (string $provider) {
    return Socialite::driver($provider)->redirect();
})->middleware('throttle:6,1');
```

### Error Handling

```php
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

Route::get('/auth/callback/{provider}', function (string $provider) {
    try {
        $user = Socialite::driver($provider)->user();
        
        // Handle user logic
        
    } catch (InvalidStateException $e) {
        return redirect('/login')->with('error', 'Authentication failed. Please try again.');
    } catch (Exception $e) {
        Log::error('Social login error: ' . $e->getMessage());
        return redirect('/login')->with('error', 'Something went wrong. Please try again.');
    }
});
```

## Custom Providers

For custom OAuth providers, extend the abstract provider:

```php
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\User;

class CustomProvider extends AbstractProvider
{
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://example.com/oauth/authorize', $state);
    }

    protected function getTokenUrl()
    {
        return 'https://example.com/oauth/token';
    }

    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get('https://example.com/api/user', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id'       => $user['id'],
            'nickname' => $user['username'],
            'name'     => $user['name'],
            'email'    => $user['email'],
            'avatar'   => $user['avatar_url'],
        ]);
    }
}
```

## Testing

### Feature Tests

```php
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User;

test('user can login with github', function () {
    $githubUser = Mockery::mock(User::class);
    $githubUser->shouldReceive('getId')->andReturn('123456');
    $githubUser->shouldReceive('getName')->andReturn('John Doe');
    $githubUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $githubUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    
    Socialite::shouldReceive('driver->user')->andReturn($githubUser);
    
    $response = $this->get('/auth/callback/github');
    
    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('users', [
        'email' => 'john@example.com',
        'provider_name' => 'github',
    ]);
});
```

## Common Pitfalls to Avoid

1. **Don't store sensitive tokens in logs**
2. **Always validate the provider parameter**
3. **Handle email conflicts gracefully**
4. **Use HTTPS for redirect URLs in production**
5. **Keep provider credentials secure and out of version control**
6. **Handle cases where users deny permission**
7. **Implement proper session management for the OAuth flow**