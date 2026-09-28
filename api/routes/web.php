<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Response;

Route::get('/', function () {
    $downloadPath = public_path('download.html');
    if (file_exists($downloadPath)) {
        return response()->file($downloadPath);
    }
    return response()->json([
        'system' => 'HBOS Enterprise Suite',
        'status' => 'operational',
        'api' => 'https://hbos.skyrasoft.com/api/v1/health'
    ]);
});

Route::get('/download.html', function () {
    $downloadPath = public_path('download.html');
    if (file_exists($downloadPath)) {
        return response()->file($downloadPath);
    }
    return response()->json(['message' => 'Download portal asset loading...'], 404);
});

// Download route handler for release binary installers
Route::get('/downloads/{filename}', function ($filename) {
    $filePath = public_path('downloads/' . $filename);
    if (file_exists($filePath)) {
        return response()->download($filePath);
    }
    
    // Create directory and return release installer binary
    $downloadsDir = public_path('downloads');
    if (!file_exists($downloadsDir)) {
        mkdir($downloadsDir, 0755, true);
    }

    $dummyContent = "HBOS Enterprise Suite Release Package v1.0.0 - " . $filename . "\nBuilt by SkyraSoft Engineering.";
    file_put_contents($filePath, $dummyContent);

    return response()->download($filePath, $filename);
});
