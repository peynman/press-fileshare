<?php

return [
    'product_typename' => 'fileshare',

    'controllers' => [
        \Larapress\FileShare\Controllers\FileUploadController::class,
    ],

    'permissions' => [
        \Larapress\FileShare\CRUD\FileUploadCRUDProvider::class,
    ],

    'routes' => [
        'file_upload' => [
            'name' => 'file-upload',
            'extend' => [
                'providers' => [
                ],
            ],
        ],
    ],

    'known_mime_types' => [
        'png', 'jpeg', 'jpg', 'mp4', 'pdf', 'zip',
    ],

    'file_upload_processors' => [
    ],
];
