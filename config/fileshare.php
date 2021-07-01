<?php

return [
    // file sharing product typename
    'product_typename' => 'fileshare',

    // acceptable mime types in uploads
    'known_mime_types' => [
        'png',
        'jpeg',
        'jpg',
        'mp4',
        'pdf',
        'zip',
    ],

    'file_upload_processors' => [],

    // crud resource in package
    'routes' => [
        'file_upload' => [
            'name' => 'file-upload',
            'model' => \Larapress\FileShare\Models\FileUpload::class,
            'provider' => \Larapress\FileShare\CRUD\FileUploadCRUDProvider::class,
        ],
    ],

    'controllers' => [
        \Larapress\FileShare\Controllers\FileUploadController::class,
    ],

    'permissions' => [
        \Larapress\FileShare\CRUD\FileUploadCRUDProvider::class,
    ],

];
