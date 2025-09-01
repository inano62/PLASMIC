<?php
if (!function_exists('frontend_origin')) {
    function frontend_origin(): string {
        return rtrim(config('app.frontend_origin', env('FRONTEND_ORIGIN', 'http://localhost:5176')), '/');
    }
}
