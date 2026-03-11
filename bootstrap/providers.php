<?php

return array_values(array_filter([
    App\Providers\AppServiceProvider::class,
    class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
        ? App\Providers\TelescopeServiceProvider::class
        : null,
]));
