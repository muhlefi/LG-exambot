<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\AiProviderException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 503);
            }
            return back()->withInput()->withErrors(['ai_error' => 'Masalah AI: ' . $e->getMessage()]);
        });

        $exceptions->render(function (\App\Exceptions\ImageGenerationException $e, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 503);
            }
            return back()->withInput()->withErrors(['image_error' => 'Gagal membuat gambar: ' . $e->getMessage()]);
        });

        $exceptions->render(function (\App\Exceptions\ExportException $e, \Illuminate\Http\Request $request) {
            return back()->withErrors(['export_error' => 'Gagal export dokumen: ' . $e->getMessage()]);
        });
    })->create();
