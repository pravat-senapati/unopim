<?php

return [

    'acl' => [
        'ai-agent'              => 'Magic AI',
        'general'               => 'General',
        'prompt'                => 'Prompt',
        'system-prompt'         => 'System Prompt',
        'execute'               => 'Execute',
        'generate'              => 'Generate',
    ],

    'menu' => [
        'ai-agent'       => 'Magic AI',
        'general'        => 'General',
        'prompt'         => 'Prompt',
        'system-prompt'  => 'System Prompt',
        'generate'       => 'Generate',
    ],

    'common' => [
        'yes'  => 'Yes',
        'no'   => 'No',
        'save' => 'Save',
        'back' => 'Back',
    ],

    'credentials' => [
        'title'          => 'AI Credentials',
        'create-title'   => 'Create Credential',
        'edit-title'     => 'Edit Credential',
        'create-btn'     => 'Create Credential',
        'create-success' => 'Credential created successfully.',
        'update-success' => 'Credential updated successfully.',
        'delete-success' => 'Credential deleted successfully.',
        'test-success'   => 'Connection verified successfully.',
        'test-failed'    => 'Connection failed. Please check your credentials.',
        'general'        => 'General',
        'settings'       => 'Settings',

        'fields' => [
            'label'              => 'Label',
            'label-placeholder'  => 'e.g. My OpenAI Key',
            'provider'           => 'Provider',
            'provider-placeholder' => 'Select provider',
            'api-url'            => 'API URL',
            'api-url-placeholder'=> 'e.g. https://api.openai.com/v1',
            'api-key'            => 'API Key',
            'api-key-placeholder'=> 'Enter your API key',
            'model'              => 'Model',
            'model-placeholder'  => 'e.g. gpt-4o, claude-sonnet-4-20250514',
            'status'             => 'Status',
        ],

        'datagrid' => [
            'id'       => 'ID',
            'label'    => 'Label',
            'provider' => 'Provider',
            'model'    => 'Model',
            'status'   => 'Status',
            'edit'     => 'Edit',
            'delete'   => 'Delete',
        ],
    ],

    'agents' => [
        'title'          => 'AI Agents',
        'create-title'   => 'Create Agent',
        'edit-title'     => 'Edit Agent',
        'create-btn'     => 'Create Agent',
        'create-success' => 'Agent created successfully.',
        'update-success' => 'Agent updated successfully.',
        'delete-success' => 'Agent deleted successfully.',
        'general'        => 'General',
        'settings'       => 'Settings',
        'prompt-config'  => 'Prompt Configuration',

        'fields' => [
            'name'                    => 'Name',
            'name-placeholder'        => 'e.g. Product Enrichment Agent',
            'description'             => 'Description',
            'description-placeholder' => 'Describe what this agent does',
            'system-prompt'           => 'System Prompt',
            'system-prompt-placeholder' => 'You are a product data enrichment assistant...',
            'credential'              => 'AI Credential',
            'credential-placeholder'  => 'Select credential',
            'max-tokens'              => 'Max Tokens',
            'max-tokens-placeholder'  => 'e.g. 4096',
            'temperature'             => 'Temperature',
            'temperature-placeholder' => 'e.g. 0.7',
            'status'                  => 'Status',
        ],

        'datagrid' => [
            'id'         => 'ID',
            'name'       => 'Name',
            'credential' => 'Credential',
            'status'     => 'Status',
            'edit'       => 'Edit',
            'delete'     => 'Delete',
        ],
    ],

    'executions' => [
        'queued' => 'Agent execution has been queued.',
    ],

    'generate' => [
        'title'                   => 'Generate Product from Image',
        'click-to-upload'         => 'Click to upload',
        'or-drag-drop'            => 'or drag and drop',
        'file-types'              => 'JPG, PNG, WebP, GIF (max 10 MB)',
        'instruction-placeholder' => 'Describe the product, its theme, materials, target audience…',
        'add-assets'              => 'Add Assets',
        'generate-btn'            => 'Generate',
        'generating'              => 'Generating…',
        'credential'              => 'AI Credential',
        'select-credential'       => 'Please select an AI credential first.',
        'success'                 => 'Product generated successfully!',
        'error-generic'           => 'Something went wrong. Please try again.',
        'result-title'            => 'Generated Product',
        'detected-product'        => 'Detected Product',
        'category'                => 'Category',
        'attributes'              => 'Attributes',
        'enrichment'              => 'Enriched Content',
        'confidence'              => 'Confidence',
        'view-product'            => 'View Product',

        'validation' => [
            'images-required'    => 'Please upload at least one image.',
            'image-invalid'      => 'Each file must be a valid image (JPG, PNG, WebP, GIF).',
            'image-too-large'    => 'Each image must be under 10 MB.',
            'credential-required'=> 'Please select an AI credential.',
        ],
    ],

    'chat' => [
        'title'                    => 'AI Assistant',
        'welcome'                  => 'How can I help you today?',
        'welcome-hint'             => 'Upload images to create products, or ask me anything about your catalog.',
        'input-placeholder'        => 'Type a message or upload an image…',
        'thinking'                 => 'AI is thinking…',
        'select-credential'        => 'Please select an AI credential first.',
        'no-response'              => 'No response received.',
        'error-generic'            => 'Something went wrong. Please try again.',
        'view-product'             => 'View Product',
        'action-create-from-image' => '📷 Create from Image',
        'action-enhance'           => '✨ Enhance Product',
        'action-seo'               => '🔍 Suggest SEO',
        'action-help'              => '❓ Help',
        'prompt-create-from-image' => 'I want to create a product from an image. Let me upload one.',
        'prompt-enhance'           => 'Help me enhance a product with better descriptions and SEO content.',
        'prompt-seo'               => 'Suggest SEO-optimized meta title, description, and keywords for my product.',
        'prompt-help'              => 'What can you help me with in this PIM system?',
    ],

];
