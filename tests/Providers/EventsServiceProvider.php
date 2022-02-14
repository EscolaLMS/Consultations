<?php

namespace EscolaLms\Consultations\Tests\Providers;

use EscolaLms\Cart\Events\CartOrderPaid;
use EscolaLms\Consultations\Listeners\ReportTermListener;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    protected $listen = [
        CartOrderPaid::class => [
            ReportTermListener::class,
        ],
    ];
}
