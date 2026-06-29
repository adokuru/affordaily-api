<?php

return [
    'enabled' => env('LOG_VIEWER_ENABLED', env('APP_ENV', 'production') !== 'production'),
];
