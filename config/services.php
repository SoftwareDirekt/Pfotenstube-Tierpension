<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | HelloCash API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for HelloCash cash register integration.
    |
    */
    
    'hellocash' => [
        'sync_enabled' => env('HELLOCASH_SYNC_ENABLED', true),
        'api_key' => env('HELLOCASH_API_KEY', ''),
        'base_url' => env('HELLOCASH_BASE_URL', 'https://api.hellocash.business/api/v1'),
        'signature_mandatory' => env('HELLOCASH_SIGNATURE_MANDATORY', false),
        'test_mode' => env('HELLOCASH_TEST_MODE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pfotenstube Homepage API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Pfotenstube Homepage API integration.
    |
    */
    'pfotenstube' => [
        'api_token' => env('PFOTENSTUBE_API_TOKEN'),
        'webhook_secret' => env('PFOTENSTUBE_WEBHOOK_SECRET'),
        'homepage_url' => env('PFOTENSTUBE_HOMEPAGE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Breeding Shelter URL
    |--------------------------------------------------------------------------
    |
    | Configuration for the Breeding Shelter URL, used for switching between systems or receive data from the Breeding Shelter.
    |
    */
    'breeding_shelter' => [
        'url' => env('BREEDING_SHELTER_URL'),
        'api_token' => env('BREEDING_SHELTER_API_TOKEN'),
    ],

];
