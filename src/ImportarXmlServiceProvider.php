<?php
namespace fabrizioquadro\importarxml;

use Illuminate\Support\ServiceProvider;

class ImportarXmlServiceProvider extends ServiceProvider
{
    public function boot(){

        $this->loadRoutesFrom(__DIR__.'/routes/web.php');

        $this->loadViewsFrom(__DIR__.'/resources/views', 'importarxml');

    }

    public function register(){

        $this->publishes([__DIR__.'/database/migrations' => database_path('migrations/')]);

    }

}
