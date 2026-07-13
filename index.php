<?php
// Fallback front controller for when the root .htaccess rewrite is not applied
// (e.g. AllowOverride off). Boots the app from public/ WITHOUT redirecting the
// browser to /public, so the URL stays http://localhost/hris/.
require __DIR__.'/public/index.php';
