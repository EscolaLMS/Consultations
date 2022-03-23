<?php

namespace EscolaLms\Consultations\Providers;

use EscolaLms\Cart\Events\OrderPaid;
use EscolaLms\Consultations\Listeners\ReportTermListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [];
}
