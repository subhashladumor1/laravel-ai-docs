<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default AI Provider
    |--------------------------------------------------------------------------
    |
    | This option controls the default AI provider that will be used by the
    | package. You may choose from one of the built-in providers below.
    |
    | Supported: "openai", "claude", "gemini"
    |
    */
    'default_provider' => env('AI_DOCS_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Supported Providers
    |--------------------------------------------------------------------------
    |
    | Define which providers are enabled, their models, and API credentials.
    |
    */
    'providers' => [
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_uri' => env('OPENAI_BASE_URI', 'https://api.openai.com/v1'),
            'default_model' => env('OPENAI_DEFAULT_MODEL', 'gpt-4.1'),
            'vision_model' => env('OPENAI_VISION_MODEL', 'gpt-4o'),
            'whisper_model' => env('OPENAI_WHISPER_MODEL', 'whisper-1'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 120),
        ],
        'claude' => [
            'api_key' => env('ANTHROPIC_API_KEY'),
            'base_uri' => env('ANTHROPIC_BASE_URI', 'https://api.anthropic.com'),
            'api_version' => env('ANTHROPIC_API_VERSION', '2023-06-01'),
            'default_model' => env('CLAUDE_DEFAULT_MODEL', 'claude-3-5-sonnet-20241022'),
            'vision_model' => env('CLAUDE_VISION_MODEL', 'claude-3-5-sonnet-20241022'),
            'timeout' => (int) env('ANTHROPIC_TIMEOUT', 120),
        ],
        'gemini' => [
            'api_key' => env('GEMINI_API_KEY'),
            'base_uri' => env('GEMINI_BASE_URI', 'https://generativelanguage.googleapis.com/v1beta'),
            'default_model' => env('GEMINI_DEFAULT_MODEL', 'gemini-1.5-pro'),
            'vision_model' => env('GEMINI_VISION_MODEL', 'gemini-1.5-pro'),
            'timeout' => (int) env('GEMINI_TIMEOUT', 120),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Aliases
    |--------------------------------------------------------------------------
    |
    | Convenient short-hand aliases that map to fully-qualified model names
    | so the fluent API stays readable: ->model('gpt-4.1')
    |
    */
    'model_aliases' => [
        'gpt-4.1' => ['provider' => 'openai', 'model' => 'gpt-4.1'],
        'gpt-4o' => ['provider' => 'openai', 'model' => 'gpt-4o'],
        'gpt-4-turbo' => ['provider' => 'openai', 'model' => 'gpt-4-turbo'],
        'claude-3-5-sonnet' => ['provider' => 'claude', 'model' => 'claude-3-5-sonnet-20241022'],
        'claude-3-5-haiku' => ['provider' => 'claude', 'model' => 'claude-3-5-haiku-20241022'],
        'claude-3-opus' => ['provider' => 'claude', 'model' => 'claude-3-opus-20240229'],
        'gemini-1.5-pro' => ['provider' => 'gemini', 'model' => 'gemini-1.5-pro'],
        'gemini-1.5-flash' => ['provider' => 'gemini', 'model' => 'gemini-1.5-flash'],
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Processing
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'scanned_detection' => env('AI_DOCS_PDF_SCANNED', true),
        'dpi' => (int) env('AI_DOCS_PDF_DPI', 150),
        'convert_to_images' => env('AI_DOCS_PDF_IMAGES', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    */
    'image' => [
        'enabled' => env('AI_DOCS_IMAGE_ENABLED', true),
        'auto_rotate' => env('AI_DOCS_IMAGE_AUTO_ROTATE', true),
        'enhance_contrast' => env('AI_DOCS_IMAGE_CONTRAST', true),
        'max_width' => (int) env('AI_DOCS_IMAGE_MAX_WIDTH', 2048),
        'max_height' => (int) env('AI_DOCS_IMAGE_MAX_HEIGHT', 2048),
        'quality' => (int) env('AI_DOCS_IMAGE_QUALITY', 90),
        'supported_formats' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Audio Processing
    |--------------------------------------------------------------------------
    */
    'audio' => [
        'enabled' => env('AI_DOCS_AUDIO_ENABLED', true),
        'supported_formats' => ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg'],
        'max_file_size_mb' => (int) env('AI_DOCS_AUDIO_MAX_MB', 25),
        'response_format' => env('AI_DOCS_AUDIO_RESPONSE_FORMAT', 'verbose_json'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ask PDF / RAG Configuration
    |--------------------------------------------------------------------------
    */
    'rag' => [
        'chunk_size' => (int) env('AI_DOCS_CHUNK_SIZE', 1000),
        'chunk_overlap' => (int) env('AI_DOCS_CHUNK_OVERLAP', 100),
        'top_k_chunks' => (int) env('AI_DOCS_TOP_K', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Language Detection
    |--------------------------------------------------------------------------
    */
    'language' => [
        'auto_detect' => env('AI_DOCS_LANG_AUTO', true),
        'default' => env('AI_DOCS_LANG_DEFAULT', 'en'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported File Types per Document Category
    |--------------------------------------------------------------------------
    */
    'supported_formats' => [
        'document' => ['pdf', 'docx', 'doc', 'txt', 'md'],
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'tiff', 'tif'],
        'audio' => ['mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm', 'ogg'],
    ],

];
