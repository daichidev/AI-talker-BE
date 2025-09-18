<?php

namespace App\Services;

use Google\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Exception; // Use the base Exception class

class GoogleAccessTokenService
{
    protected Client $client;
    protected string $serviceAccountPath;
    protected array $scopes;

    public function __construct()
    {
        $this->serviceAccountPath = storage_path(config('services.firebase.service_account_path'));

        if (!File::exists($this->serviceAccountPath)) {
            throw new Exception("Firebase Service Account key file not found at: " . $this->serviceAccountPath);
        }

        // Define the necessary scope for Firebase Cloud Messaging
        $this->scopes = [
            'https://www.googleapis.com/auth/firebase.messaging',
        ];

        $this->client = new Client();
        $this->client->setAuthConfig($this->serviceAccountPath);
        $this->client->setScopes($this->scopes);
        // For service accounts, setAccessType('offline') is often default behavior for token generation
        $this->client->setAccessType('offline');
    }

    public function getAccessToken(): string
    {
        $cacheKey = 'google_access_token_fcm';

        // Attempt to retrieve token from cache
        // Cache for 59 minutes (3540 seconds) to ensure it's refreshed before actual expiration
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fetch new token
        $this->client->fetchAccessTokenWithAssertion();
        $accessToken = $this->client->getAccessToken();

        if (empty($accessToken) || !isset($accessToken['access_token'])) {
            throw new Exception("Failed to obtain access token from Google.");
        }

        // Cache the token with its expiration time (typically 3600 seconds = 1 hour)
        // Store for slightly less than its validity to proactively refresh
        $expiresIn = $accessToken['expires_in'] ?? 3600;
        Cache::put($cacheKey, $accessToken['access_token'], now()->addSeconds($expiresIn - 60));

        return $accessToken['access_token'];
    }
}
