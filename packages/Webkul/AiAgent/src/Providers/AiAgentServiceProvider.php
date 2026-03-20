<?php

namespace Webkul\AiAgent\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Webkul\AiAgent\Contracts\AgentServiceContract;
use Webkul\AiAgent\Contracts\PromptBuilderContract;
use Webkul\AiAgent\Services\AgentService;
use Webkul\AiAgent\Services\PromptBuilder;
use Webkul\AiAgent\Services\EnrichmentService;
use Webkul\AiAgent\Services\ImageToProductService;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\AiAgent\Services\VisionService;

class AiAgentServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(Router $router): void
    {
        // Routes — Route::middleware('web')->group() — NOT loadRoutesFrom()
        Route::middleware('web')->group(
            __DIR__ . '/../../Routes/ai-agent-routes.php'
        );

        // Views
        $this->loadViewsFrom(__DIR__ . '/../../Resources/views', 'ai-agent');

        // Translations
        $this->loadTranslationsFrom(__DIR__ . '/../../Resources/lang', 'ai-agent');

        // Migrations — Database/Migration/ (singular)
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migration');

        // Inject assets into admin head — event name ends with .before
        Event::listen('unopim.admin.layout.head.before', function ($viewRenderEventManager) {
            $viewRenderEventManager->addTemplate('ai-agent::layouts.head');
        });

        // Inject the global AI chat widget only for authenticated admin users
        Event::listen('unopim.admin.layout.content.after', function ($viewRenderEventManager) {
            if (auth()->guard('admin')->check()) {
                $viewRenderEventManager->addTemplate('ai-agent::components.chat-widget');
            }
        });
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(ModuleServiceProvider::class);

        $this->registerConfig();
        $this->registerBindings();
    }

    /**
     * Register config files.
     */
    protected function registerConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../Config/acl.php', 'acl');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/menu.php', 'menu.admin');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/exporters.php', 'exporters');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/quick_exporters.php', 'quick_exporters');
        $this->mergeConfigFrom(__DIR__ . '/../../Config/importers.php', 'importers');
    }

    /**
     * Register contract bindings.
     */
    protected function registerBindings(): void
    {
        $this->app->bind(AgentServiceContract::class, AgentService::class);
        $this->app->bind(PromptBuilderContract::class, PromptBuilder::class);

        // Singletons: reuse the shared AiApiClient within a request lifecycle.
        $this->app->singleton(VisionService::class);
        $this->app->singleton(EnrichmentService::class);
        $this->app->singleton(ProductWriterService::class);
        $this->app->singleton(ImageToProductService::class);
    }
}
