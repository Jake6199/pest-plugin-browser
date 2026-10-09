<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

it('serves the bytes of a streamed response unchanged', function (): void {
    // A PNG signature, bytes that are not valid UTF-8 and surrounding whitespace.
    $bytes = " \n\x89PNG\r\n\x1a\n\x00\xff\xfe binary \r\n";

    Route::get('/', fn (): string => 'home');
    Route::get('/download', fn () => response()->streamDownload(function () use ($bytes): void {
        echo $bytes;
    }, 'image.png'));

    $page = visit('/');

    $page->assertScript(<<<'JS'
        fetch('/download')
            .then(response => response.arrayBuffer())
            .then(buffer => [...new Uint8Array(buffer)].map(byte => byte.toString(16).padStart(2, '0')).join(''))
        JS, bin2hex($bytes));
});

it('serves the bytes of a file download unchanged', function (): void {
    // A PNG signature, bytes that are not valid UTF-8 and surrounding whitespace.
    $bytes = " \n\x89PNG\r\n\x1a\n\x00\xff\xfe binary \r\n";

    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    file_put_contents($tempFile, $bytes);

    Route::get('/', fn (): string => 'home');
    Route::get('/download', fn () => response()->download($tempFile, 'image.png'));

    $page = visit('/');

    $page->assertScript(<<<'JS'
        fetch('/download')
            .then(response => response.arrayBuffer())
            .then(buffer => [...new Uint8Array(buffer)].map(byte => byte.toString(16).padStart(2, '0')).join(''))
        JS, bin2hex($bytes));

    unlink($tempFile);
});
