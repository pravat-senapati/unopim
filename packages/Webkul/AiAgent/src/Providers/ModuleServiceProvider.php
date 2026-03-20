<?php

namespace Webkul\AiAgent\Providers;

use Webkul\Core\Providers\CoreModuleServiceProvider;

class ModuleServiceProvider extends CoreModuleServiceProvider
{
    /**
     * Models to register their repository bindings.
     *
     * @var array
     */
    protected $models = [
        \Webkul\AiAgent\Models\Credential::class,
        \Webkul\AiAgent\Models\Agent::class,
        \Webkul\AiAgent\Models\AgentExecution::class,
    ];
}
