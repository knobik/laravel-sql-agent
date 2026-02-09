---
title: Introduction
description: Overview of Laravel SQL Agent and how it works.
sidebar:
  order: 1
---

Laravel SQL Agent is a self-learning text-to-SQL agent for Laravel that converts natural language questions into SQL queries using LLMs.

This package is based on [Dash](https://github.com/agno-agi/dash) and [OpenAI's in-house data agent](https://openai.com/index/inside-our-in-house-data-agent/).

## How It Works

1. User asks a question via `SqlAgent::run()` or the web UI
2. The `ContextBuilder` assembles context — schema, business rules, learnings
3. The agent enters a tool-calling loop with the LLM
4. Tools (`IntrospectSchemaTool`, `SearchKnowledgeTool`, `RunSqlTool`, `SaveLearningTool`) gather data and execute SQL
5. On SQL errors, the auto-learning system captures patterns for future queries
6. The agent returns a structured response with the answer, SQL, and results

## Requirements

- PHP 8.2 or higher
- Laravel 11.x or 12.x
- An LLM API key (OpenAI, Anthropic, or local Ollama installation)
- Optional: Livewire 3.x for the chat UI
- Optional: Laravel Scout for external search engines
