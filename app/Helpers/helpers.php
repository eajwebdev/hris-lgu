<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('pdf_image')) {
    /**
     * Embed an image in a PDF as a data URI.
     *
     * PDF views used asset(), which hands dompdf an absolute URL — dompdf then
     * fetches every image over HTTP from the very server that is busy rendering
     * the PDF. On a single-worker server that deadlocks until the request times
     * out; everywhere else it is still a round-trip per image. Reading the file
     * straight off disk removes both problems.
     *
     * The encoded image is cached for the life of the request, so a header that
     * appears on every page is only read once.
     *
     * @param  string  $path  Path relative to public/, e.g. 'Uploads/dtr-header.png'
     */
    function pdf_image(string $path): string
    {
        static $cache = [];

        if (array_key_exists($path, $cache)) {
            return $cache[$path];
        }

        $file = public_path($path);

        if (!is_file($file)) {
            return $cache[$path] = '';
        }

        $mime = match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif'         => 'image/gif',
            'svg'         => 'image/svg+xml',
            default       => 'image/png',
        };

        return $cache[$path] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($file));
    }
}

if (!function_exists('guard')) {
    function guard()
    {
        if (Auth::guard('web')->check()) {
            return 'web';
        } elseif (Auth::guard('employee')->check()) {
            return 'employee';
        }
        return null;
    }
}

if (!function_exists('shortEncrypt')) {
    function shortEncrypt($string)
    {
        $key = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';
        return rtrim(strtr(base64_encode(openssl_encrypt($string, $cipher, $key, 0)), '+/', '-_'), '=');
    }
}

if (!function_exists('shortDecrypt')) {
    function shortDecrypt($encrypted)
    {
        $key = 'fA7xB93kL0pTzWmQ';
        $cipher = 'AES-128-ECB';
        $encrypted = strtr($encrypted, '-_', '+/');
        return openssl_decrypt(base64_decode($encrypted), $cipher, $key, 0);
    }
}




