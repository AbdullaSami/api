<?php

use App\Http\Controllers\Api\Admin\LayoutSectionBlockController;
use App\Http\Controllers\Api\Admin\LayoutSectionController;
use App\Http\Controllers\Api\LandingPageController;
use Illuminate\Support\Facades\Route;

Route::get('/landing-page', [LandingPageController::class, 'index']);

Route::middleware(['auth:sanctum'])->prefix('admin/landing-page')->group(function () {
    Route::get('/sections', [LayoutSectionController::class, 'index']);
    Route::patch('/sections/reorder', [LayoutSectionController::class, 'reorder']);
    Route::patch('/sections/{section}/toggle', [LayoutSectionController::class, 'toggle']);

    Route::patch('/blocks/{block}', [LayoutSectionBlockController::class, 'update']);
    Route::patch('/sections/{section}/blocks/reorder', [LayoutSectionBlockController::class, 'reorder']);
});
