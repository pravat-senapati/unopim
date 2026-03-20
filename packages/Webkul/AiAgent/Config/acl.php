<?php

// ACL — flat array, no nested children

return [
    [
        'key'   => 'ai-agent',
        'name'  => 'ai-agent::app.acl.ai-agent',
        'route' => 'admin.configuration.edit',
        'sort'  => 10,
    ],
    [
        'key'    => 'ai-agent.general',
        'name'   => 'ai-agent::app.acl.general',
        'route'  => 'admin.configuration.edit',
        'sort'   => 1,
    ],
    [
        'key'    => 'ai-agent.prompt',
        'name'   => 'ai-agent::app.acl.prompt',
        'route'  => 'admin.magic_ai.prompt.index',
        'sort'   => 2,
    ],
    [
        'key'    => 'ai-agent.system-prompt',
        'name'   => 'ai-agent::app.acl.system-prompt',
        'route'  => 'admin.magic_ai.system_prompt.index',
        'sort'   => 3,
    ],

    [
        'key'   => 'ai-agent.generate',
        'name'  => 'ai-agent::app.acl.generate',
        'route' => 'ai-agent.generate.index',
        'sort'  => 5,
    ],
    [
        'key'   => 'ai-agent.execute',
        'name'  => 'ai-agent::app.acl.execute',
        'route' => 'ai-agent.execute',
        'sort'  => 6,
    ],
];
