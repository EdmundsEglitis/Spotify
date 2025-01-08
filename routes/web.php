<?php
use App\Http\Controllers\MoodController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FaceDetectionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;





Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/moods', [MoodController::class, 'index'])->name('moods.index');
    Route::post('/moods', [MoodController::class, 'store'])->name('moods.store');
    Route::get('/moods/history', [MoodController::class, 'history'])->name('moods.history');
    Route::get('/dash', [MoodController::class, 'mooddash'])->name('moods.dash');

    Route::post('/detect', [FaceDetectionController::class, 'detect'])->name('moods.detect');
    Route::get('/camera', [FaceDetectionController::class, 'show'])->name('moods.show');
});


Route::get('/login/spotify', [MoodController::class, 'redirectToSpotify'])->name('spotify.login');
Route::get('/callback', [MoodController::class, 'handleSpotifyCallback']);

require __DIR__.'/auth.php';
