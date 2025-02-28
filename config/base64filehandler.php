<?php

// config for AwaisJameel/Base64FileHandler
return [
    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | This value determines the default disk that will be used for storing
    | files decoded from base64 data.
    |
    */
    'disk' => env('BASE64_FILE_HANDLER_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Default Storage Path
    |--------------------------------------------------------------------------
    |
    | This value determines the default path within the disk where files
    | will be stored.
    |
    */
    'path' => env('BASE64_FILE_HANDLER_PATH', 'uploads/'),

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    |
    | This array contains the allowed file extensions. If this array is empty,
    | all extensions are allowed. To restrict uploads to specific file
    | extensions, add them to this array.
    |
    */
    'allowed_extensions' => [
        // 'pdf', 'jpg', 'png', 'docx', etc.
    ],

    /*
    |--------------------------------------------------------------------------
    | Valid Image Extensions
    |--------------------------------------------------------------------------
    |
    | This array contains the valid image extensions that will be recognized
    | when validating images.
    |
    */
    'valid_image_extensions' => [
        'jpeg',
        'jpg',
        'png',
        'gif',
        'webp',
    ],
];
