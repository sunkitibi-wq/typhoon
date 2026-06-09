<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Barryvdh\DomPDF\ServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    ServiceProvider::class,
];
