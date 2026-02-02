<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Display Name
    |--------------------------------------------------------------------------
    |
    | The display name used in the UI and logs.
    |
    */
    'name' => 'SqlAgent',

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database connections for SqlAgent.
    | - connection: The database connection to query (your data)
    | - storage_connection: The connection for SqlAgent's own tables
    |
    */
    'database' => [
        'connection' => env('SQL_AGENT_CONNECTION', config('database.default')),
        'storage_connection' => env('SQL_AGENT_STORAGE_CONNECTION', config('database.default')),
    ],

    /*
    |--------------------------------------------------------------------------
    | LLM Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the Large Language Model driver and settings.
    |
    */
    'llm' => [
        'default' => env('SQL_AGENT_LLM_DRIVER', 'openai'),

        'drivers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'),
                'model' => env('SQL_AGENT_OPENAI_MODEL', 'gpt-4o'),
                'embedding_model' => env('SQL_AGENT_OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
                'temperature' => 0.0,
                'max_tokens' => 4096,
            ],

            'anthropic' => [
                'api_key' => env('ANTHROPIC_API_KEY'),
                'model' => env('SQL_AGENT_ANTHROPIC_MODEL', 'claude-sonnet-4-20250514'),
                'temperature' => 0.0,
                'max_tokens' => 4096,
            ],

            'ollama' => [
                'base_url' => env('OLLAMA_BASE_URL', 'http://localhost:11434'),
                'model' => env('SQL_AGENT_OLLAMA_MODEL', 'llama3.1'),
                'embedding_model' => env('SQL_AGENT_OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
                'temperature' => 0.0,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the search driver for semantic search.
    |
    */
    'search' => [
        'default' => env('SQL_AGENT_SEARCH_DRIVER', 'database'),

        'drivers' => [
            'database' => [
                'min_similarity' => 0.3,
            ],

            'scout' => [
                'driver' => env('SCOUT_DRIVER', 'meilisearch'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Agent Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the SQL agent behavior.
    |
    */
    'agent' => [
        'max_iterations' => env('SQL_AGENT_MAX_ITERATIONS', 10),
        'default_limit' => env('SQL_AGENT_DEFAULT_LIMIT', 100),
        'chat_history_length' => env('SQL_AGENT_CHAT_HISTORY', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Learning Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the self-learning features.
    |
    */
    'learning' => [
        'enabled' => env('SQL_AGENT_LEARNING_ENABLED', true),
        'auto_save_errors' => env('SQL_AGENT_AUTO_SAVE_ERRORS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Knowledge Configuration
    |--------------------------------------------------------------------------
    |
    | Configure knowledge base settings.
    |
    */
    'knowledge' => [
        'path' => env('SQL_AGENT_KNOWLEDGE_PATH', resource_path('sql-agent/knowledge')),
        'source' => env('SQL_AGENT_KNOWLEDGE_SOURCE', 'files'), // 'files' or 'database'
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the web interface.
    |
    */
    'ui' => [
        'enabled' => env('SQL_AGENT_UI_ENABLED', true),
        'route_prefix' => env('SQL_AGENT_ROUTE_PREFIX', 'sql-agent'),
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQL Safety Configuration
    |--------------------------------------------------------------------------
    |
    | Configure SQL safety rules and limits.
    |
    */
    'sql' => [
        'allowed_statements' => ['SELECT', 'WITH'],

        'forbidden_keywords' => [
            'DROP',
            'DELETE',
            'UPDATE',
            'INSERT',
            'ALTER',
            'CREATE',
            'TRUNCATE',
            'GRANT',
            'REVOKE',
            'EXEC',
            'EXECUTE',
        ],

        'max_rows' => env('SQL_AGENT_MAX_ROWS', 1000),
    ],
];
