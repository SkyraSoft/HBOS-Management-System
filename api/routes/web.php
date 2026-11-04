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
    $cleanFilename = basename($filename);
    
    // Redirect legacy dummy EXE to the authentic WiX MSI package
    if ($cleanFilename === 'HBOS_Setup_v1.0.0_x64.exe') {
        return redirect('/downloads/HBOS_POS_1.0.0_x64_en-US.msi', 301);
    }
    
    $filePath = public_path('downloads/' . $cleanFilename);
    if (file_exists($filePath)) {
        return response()->download($filePath, $cleanFilename);
    }
    
    return response()->json([
        'error' => 'Not Found',
        'message' => 'The requested release distribution package does not exist on this server.',
        'file' => $cleanFilename
    ], 404);
});

// Web Application / Demo Single Page App
Route::get('/app/{any?}', function () {
    $appPath = public_path('app/index.html');
    if (file_exists($appPath)) {
        return response()->file($appPath, [
            'Content-Type' => 'text/html; charset=UTF-8'
        ]);
    }
    return response()->json(['error' => 'Web application assets not found.'], 404);
})->where('any', '.*');
