# Dexcom provider for Laravel Socialite 

```bash
composer require chrismou/dexcom-socialite-provider
```

## Installation & Basic Usage

Please see the [Base Installation Guide](https://socialiteproviders.com/usage/), then follow the provider specific instructions below.

### Add configuration to `config/services.php`

Add the required values to your `.env` and then add the below configuration.  The `mode` can be one of `us`, `eu`, `jp` or `sandbox` (default is `sandbox`). You'll need to develop with
`sandbox` intially, but when you switch to using production data you'll need to change this to the appropriate region - `us` if in America, `jp` for Japan or `eu` for the rest of the world.

```php
'dexcom' => [    
  'client_id' => env('DEXCOM_CLIENT_ID'),  
  'client_secret' => env('DEXCOM_CLIENT_SECRET'),  
  'redirect' => env('DEXCOM_REDIRECT_URI'),
  'mode' => env('DEXCOM_MODE', 'sandbox'), // 'us', 'eu', jp' or 'sandbox'
],
```

### Add provider event listener

#### Laravel 11+

In Laravel 11, the default `EventServiceProvider` provider was removed. Instead, add the listener using the `listen` method on the `Event` facade, in your `AppServiceProvider` `boot` method.

* Note: You do not need to add anything for the built-in socialite providers unless you override them with your own providers.

```php
Event::listen(function (\SocialiteProviders\Manager\SocialiteWasCalled $event) {
    $event->extendSocialite('dexcom', \SocialiteProviders\Dexcom\src\Provider::class);
});
```
<details>
<summary>
Laravel 10 or below
</summary>
Configure the package's listener to listen for `SocialiteWasCalled` events.

Add the event to your `listen[]` array in `app/Providers/EventServiceProvider`. See the [Base Installation Guide](https://socialiteproviders.com/usage/) for detailed instructions.

```php
protected $listen = [
    \SocialiteProviders\Manager\SocialiteWasCalled::class => [
        // ... other providers
        \SocialiteProviders\Dexcom\DexcomExtendSocialite::class.'@handle',
    ],
];
```
</details>

### Usage example

You should now be able to use the provider like you would regularly use Socialite (assuming you have the facade installed), ie:

```php
Route::get('/auth/dexcom', function () {
    return Socialite::driver('dexcom')->redirect();
});
```

Assuming you registered /auth/callback as your redirect URL, you can handle the callback like so:
```php
Route::get('/auth/callback', function () {
    $dexcomUser = Socialite::driver('dexcom')->user();
    $user = User::updateOrCreate([
        'dexcom_user_id' => $dexcomUser->id,
    ], [
        'dexcom_access_token' => $dexcomUser->token,
        'dexcom_refresh_token' => $dexcomUser->refreshToken,
    ]);

    Auth::login($user, true);

    return redirect()->intended('/dashboard');
});
```
Now you should have a user logged in with their Dexcom account, and an access/refresh token available for API requests

```php
Route::get('/dashboard', function () {
    $user = Auth::user();

    $client = new \GuzzleHttp\Client\Client();
    $request = $client->request('GET', Socialite::driver('dexcom')->getApiUrl() . '/v3/users/self/egvs', [
        'query' => [
            'startDate' => now()->subDay()->format('Y-m-d\TH:i:s'),
            'endDate' => now()->format('Y-m-d\TH:i:s'),
        ],
        'headers' => [
            'Authorization' => 'Bearer ' . $user->dexcom_access_token,
            'Accept' => 'application/json',
        ]
    ]);
    
    var_dump(json_decode($request->getBody()->getContents());
    exit;
    
})->middleware('auth');
```

The above example assumes you've added the following fields to your `users` table:

```php
$table->string('dexcom_user_id')->unique();
$table->string('dexcom_access_token', 1000)->nullable();
$table->string('dexcom_refresh_token')->nullable();
```

### Returned User fields

The Dexcom API doesn't provide a user endpoint so the user ID is retrieved by a call to the `devices` endpoint. Therefore, the only fields returned are `id`, which should at
least be enough to ensure the you can match the dexcom to a laravel user.