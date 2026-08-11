<?php

namespace App\Providers;

use App\Support\OcmsAccess;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerOcmsBladeDirectives();
    }

    protected function registerOcmsBladeDirectives(): void
    {
        Blade::if('ocmsOperate', fn () => auth()->user()?->canOperateOverhaul() ?? false);
        Blade::if('ocmsApprove', fn () => auth()->user()?->canApproveStages() ?? false);
        Blade::if('ocmsRegister', fn () => auth()->user()?->canRegisterComponents() ?? false);
        Blade::if('ocmsWarehouse', fn () => auth()->user()?->canManageWarehouse() ?? false);
        Blade::if('ocmsExecutive', fn () => auth()->user()?->canViewExecutiveDashboard() ?? false);
        Blade::if('ocmsAdmin', fn () => auth()->user()?->canManageUsers() ?? false);
        Blade::if('ocmsDeveloper', fn () => auth()->user()?->canManageTemplates() ?? false);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
