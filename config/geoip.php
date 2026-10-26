<?php

return [

    'log_failures' => false,

    'include_currency' => true,

    'service' => 'maxmind_database',

    'services' => [

        'maxmind_database' => [
            'class' => \Torann\GeoIP\Services\MaxMindDatabase::class,
            'database_path' => storage_path('app/geoip.mmdb'),
            'update_url' => sprintf('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&license_key=%s&suffix=tar.gz', env('MAXMIND_LICENSE_KEY')),
            'locales' => ['en'],
        ],

        'maxmind_api' => [
            'class' => \Torann\GeoIP\Services\MaxMindWebService::class,
            'user_id' => env('MAXMIND_USER_ID'),
            'license_key' => env('MAXMIND_LICENSE_KEY'),
            'locales' => ['en'],
        ],

        'ipgeolocation' => [
            'class' => \Torann\GeoIP\Services\IPGeoLocation::class,
            'secure' => true,
            'key' => env('IPGEOLOCATION_KEY'),
            'continent_path' => storage_path('app/continents.json'),
            'lang' => 'en',
        ],

        'ipdata' => [
            'class' => \Torann\GeoIP\Services\IPData::class,
            'key' => env('IPDATA_API_KEY'),
            'secure' => true,
        ],

        'ipfinder' => [
            'class' => \Torann\GeoIP\Services\IPFinder::class,
            'key' => env('IPFINDER_API_KEY'),
            'secure' => true,
            'locales' => ['en'],
        ],

    ],

    'cache' => 'none',

    'cache_tags' => ['torann-geoip-location'],

    'cache_expires' => 30,

    'default_location' => [
        'ip'          => '127.0.0.0',
        'iso_code'    => 'IR',
        'country'     => 'Iran',
        'city'        => 'Tehran',
        'state'       => 'TH',
        'state_name'  => 'Tehran',
        'postal_code' => '00000',
        'lat'         => 35.69,
        'lon'         => 51.42,
        'timezone'    => 'Asia/Tehran',
        'continent'   => 'AS',
        'default'     => true,
        'currency'    => 'IRR',
    ],

];
