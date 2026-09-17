<?php

return [
    'scanner' => env('SIMON_SCANNER', env('APP_ENV') === 'production' ? 'clamav' : 'development'),
    'scanner_binary' => env('SIMON_SCANNER_BINARY', 'clamscan'),
    'media_async' => (bool) env('SIMON_MEDIA_ASYNC', env('APP_ENV') !== 'testing'),
    'reminder_timezone' => 'Asia/Jakarta',
    'reminder_hour' => 8,
    'document_header' => env('SIMON_DOCUMENT_HEADER', 'SIMON — Sistem Informasi Barang Milik Negara'),
    'templates_approved' => (bool) env('SIMON_TEMPLATES_APPROVED', false),
];
