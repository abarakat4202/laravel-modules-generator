<?php

namespace Abarakat\ModuleGenerator\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Westore\ModuleGenerator\ModuleMake;

class ModuleGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/module-generator.php' => config_path('module-generator.php'),
        ], 'module-generator-config');        

        if ($this->app->runningInConsole()) {
            $this->bootCommands();
        }
    }

    public function bootCommands()
    {
        foreach (array_keys(config('module-generator.allowed_types')) as $type) 
        {
            Artisan::command("create:$type {name} {module} {baseDir=Modules}", function($name, $module, $baseDir)
            {
                $commandName = Arr::get(request()->server(), 'argv.1');

                $createType = explode(':', $commandName)[1];
                
                $outputs = app()->make(ModuleMake::class)
                                ->handle(
                                    type: $createType,
                                    name: $name,
                                    module: $module,
                                    baseDir: $baseDir,
                                );
                foreach($outputs as $output)
                {
                    $this->info($output);
                }
                
            })
            ->purpose("Ceate a new module's $type");
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/module-generator.php', 'module-generator'
        );
    }
}