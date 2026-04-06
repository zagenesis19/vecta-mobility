<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisteredUserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para validación previa de registro
Route::post('/validate-register-step', [RegisteredUserController::class, 'validateStep']);

Route::get('/municipalities', [\App\Http\Controllers\MunicipalityController::class, 'index']);

Route::get('/debug-register', function() {
    try {
        return \App\Models\User::create([
            'name' => 'Debug User',
            'email' => 'debug'.rand(100,999).'@example.com',
            'password' => '$2y$12$UsingHashHere...', // dummy
            'role' => 'passenger',
            'phone_number' => '58412'.rand(1000000,9999999), 
            'gender' => 'male',
            'terms_accepted' => true,
            'country' => 'Venezuela',
            'state' => 'Miranda',
            'municipality' => 'Test',
            'phone_verified_at' => now(),
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 500);
    }
});
