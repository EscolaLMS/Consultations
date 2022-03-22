<?php

namespace EscolaLms\Consultations\Providers;

use EscolaLms\Cart\Events\OrderPaid;
use EscolaLms\Consultations\Listeners\ReportTermListener;
use Illuminate\Support\ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderPaid::class => [
            ReportTermListener::class,
        ],
    ];
}
