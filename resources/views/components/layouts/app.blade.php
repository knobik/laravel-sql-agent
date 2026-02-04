<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))" :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('sql-agent.name', 'SQL Agent') }}</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js is included with Livewire 3 -->

    <!-- Highlight.js for SQL syntax highlighting -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css" class="dark:block hidden">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css" class="dark:hidden block">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/sql.min.js"></script>

    <!-- Marked.js for Markdown rendering -->
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>

    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }

        /* Custom scrollbar */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #475569;
        }

        /* Markdown content styles */
        .markdown-content p { margin-bottom: 0.5rem; }
        .markdown-content ul, .markdown-content ol { margin-left: 1.5rem; margin-bottom: 0.5rem; }
        .markdown-content ul { list-style-type: disc; }
        .markdown-content ol { list-style-type: decimal; }
        .markdown-content code:not(pre code) {
            background: #f1f5f9;
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        .dark .markdown-content code:not(pre code) {
            background: #334155;
        }
        .markdown-content pre {
            margin: 0.5rem 0;
            border-radius: 0.375rem;
            overflow-x: auto;
        }
        .markdown-content pre code {
            display: block;
            padding: 0.75rem 1rem;
        }

        /* Tool execution tags */
        .markdown-content tool {
            display: inline-flex;
            align-items: center;
            gap: 0.375rem;
            font-size: 0.75rem;
            padding: 0.25rem 0.625rem;
            margin: 0.25rem 0;
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 9999px;
            font-weight: 500;
        }
        .markdown-content tool[data-sql] {
            cursor: pointer;
        }
        .markdown-content tool[data-sql]:hover {
            background: #bae6fd;
        }
        .markdown-content tool[data-sql]::after {
            content: 'Show';
            font-size: 0.65rem;
            padding: 0.125rem 0.375rem;
            margin-left: 0.125rem;
            background: rgba(3, 105, 161, 0.15);
            border-radius: 0.25rem;
            font-weight: 600;
        }
        .markdown-content tool[data-sql].expanded::after {
            content: 'Hide';
        }
        .markdown-content .tool-sql-preview {
            display: block;
            margin: 0.25rem 0 0.5rem 0;
            padding: 0.75rem;
            background: #f1f5f9;
            border-radius: 0.5rem;
            font-family: ui-monospace, monospace;
            font-size: 0.75rem;
            white-space: pre-wrap;
            word-break: break-all;
            color: #334155;
            border-left: 3px solid #0ea5e9;
        }
        .dark .markdown-content tool[data-sql]:hover {
            background: #075985;
        }
        .dark .markdown-content tool[data-sql]::after {
            background: rgba(125, 211, 252, 0.15);
        }
        .dark .markdown-content .tool-sql-preview {
            background: #1e293b;
            color: #e2e8f0;
            border-left-color: #38bdf8;
        }
        .markdown-content tool::before {
            content: '';
            display: inline-block;
            width: 0.875rem;
            height: 0.875rem;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }
        .markdown-content tool[data-type="sql"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230369a1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E");
        }
        .markdown-content tool[data-type="schema"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230369a1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'/%3E%3C/svg%3E");
        }
        .markdown-content tool[data-type="search"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230369a1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'/%3E%3C/svg%3E");
        }
        .markdown-content tool[data-type="save"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230369a1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4'/%3E%3C/svg%3E");
        }
        .markdown-content tool[data-type="default"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%230369a1'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'/%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/%3E%3C/svg%3E");
        }
        .dark .markdown-content tool {
            background: #0c4a6e;
            color: #7dd3fc;
        }
        .dark .markdown-content tool[data-type="sql"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237dd3fc'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'/%3E%3C/svg%3E");
        }
        .dark .markdown-content tool[data-type="schema"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237dd3fc'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4'/%3E%3C/svg%3E");
        }
        .dark .markdown-content tool[data-type="search"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237dd3fc'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253'/%3E%3C/svg%3E");
        }
        .dark .markdown-content tool[data-type="save"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237dd3fc'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4'/%3E%3C/svg%3E");
        }
        .dark .markdown-content tool[data-type="default"]::before {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%237dd3fc'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z'/%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 12a3 3 0 11-6 0 3 3 0 016 0z'/%3E%3C/svg%3E");
        }

        /* Loading dots animation */
        .loading-dots span {
            animation: loadingDots 1.4s infinite both;
        }
        .loading-dots span:nth-child(2) { animation-delay: 0.2s; }
        .loading-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes loadingDots {
            0%, 80%, 100% { opacity: 0; }
            40% { opacity: 1; }
        }

        /* Stream cursor */
        .stream-cursor::after {
            content: '|';
            animation: blink 1s step-end infinite;
        }
        @keyframes blink {
            50% { opacity: 0; }
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen">
    <div class="flex h-screen overflow-hidden">
        {{ $slot }}
    </div>

    @livewireScripts

    <script>
        // Configure marked.js to allow HTML (for tool tags)
        marked.setOptions({
            breaks: true,
            gfm: true,
        });

        // Initialize highlight.js
        document.addEventListener('livewire:navigated', () => {
            hljs.highlightAll();
        });

        // Re-highlight on Livewire updates
        Livewire.hook('morph.updated', ({ el }) => {
            el.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl+Enter or Cmd+Enter to send message
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const sendButton = document.querySelector('[data-send-button]');
                if (sendButton && !sendButton.disabled) {
                    sendButton.click();
                }
            }

            // Ctrl+N or Cmd+N for new conversation
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                Livewire.dispatch('new-conversation');
            }
        });

        // Handle tool SQL preview toggle
        document.addEventListener('click', function(e) {
            const tool = e.target.closest('tool[data-sql]');
            if (!tool) return;

            const sql = tool.dataset.sql;
            const existingPreview = tool.nextElementSibling;

            // Toggle off if already expanded
            if (tool.classList.contains('expanded')) {
                tool.classList.remove('expanded');
                if (existingPreview && existingPreview.classList.contains('tool-sql-preview')) {
                    existingPreview.remove();
                }
                return;
            }

            // Create and insert preview
            tool.classList.add('expanded');
            const preview = document.createElement('div');
            preview.className = 'tool-sql-preview';
            preview.textContent = sql;
            tool.insertAdjacentElement('afterend', preview);

            // Highlight the SQL
            if (window.hljs) {
                const code = document.createElement('code');
                code.className = 'language-sql';
                code.textContent = sql;
                preview.innerHTML = '';
                preview.appendChild(code);
                hljs.highlightElement(code);
            }
        });
    </script>
</body>
</html>
