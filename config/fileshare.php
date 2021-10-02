<?php

return [
    'product_typename' => 'fileshare',

    // valid file upload mime types
    'known_mime_types' => [
        'png',
        'jpeg',
        'jpg',
        'mp4',
        'pdf',
        'zip',
    ],

    // default disk used for public file uploads
    'default_public_disk' => 'public',

    // default disk used for private file uploads
    'default_private_disk' => 'local',

    // classes to post process files
    'file_upload_processors' => [
        Larapress\FileShare\Services\ImageThumbnail\ImageThumbnailProcessor::class,
        // Larapress\VOD\Services\VOD\VideoFileProcessor::class,
        // Larapress\SAzmoon\Services\Azmoon\AzmoonZipFileProcessor::class,
    ],

    // image thumbnail generato file processor
    'image_thumbnail_processor' => [
        'thumbnails' => [
            // thumbnail prefix and their maximum width
            'x' => 256,
            'xx' => 128,
            'xxx' => 64,
        ],
        // queue name to run jobs
        'queu' => 'jobs',
    ],

    // crud resources of the package
    'routes' => [
        'file_upload' => [
            'name' => 'file-upload',
            'model' => \Larapress\FileShare\Models\FileUpload::class,
            'provider' => \Larapress\FileShare\CRUD\FileUploadCRUDProvider::class,
        ],
    ],

    'permissions' => [
        \Larapress\FileShare\CRUD\FileUploadCRUDProvider::class,
    ],

];
