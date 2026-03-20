<?php

return [
    [
        'key'    => 'ai-agent',
        'name'   => 'ai-agent::app.menu.ai-agent',
        'route'  => 'admin.configuration.edit',
        'params' => ['general', 'magic_ai'],
        'sort'   => 8,
        'icon'   => 'icon-magic-ai',
    ],
    [
        'key'    => 'ai-agent.general',
        'name'   => 'ai-agent::app.menu.general',
        'route'  => 'admin.configuration.edit',
        'params' => ['general', 'magic_ai'],
        'sort'   => 1,
        'icon'   => '',
    ],
    [
        'key'    => 'ai-agent.prompt',
        'name'   => 'ai-agent::app.menu.prompt',
        'route'  => 'admin.magic_ai.prompt.index',
        'sort'   => 2,
        'icon'   => '',
    ],
    [
        'key'    => 'ai-agent.system-prompt',
        'name'   => 'ai-agent::app.menu.system-prompt',
        'route'  => 'admin.magic_ai.system_prompt.index',
        'sort'   => 3,
        'icon'   => '',
    ],
];
