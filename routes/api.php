<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Livestream start/end and chat live in routes/web.php (streams.*), behind login.
// The unauthenticated WebRTC signalling routes that used to be here pointed at
// methods that never existed and were removed.
