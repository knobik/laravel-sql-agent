---
title: Database Support
description: Supported database engines and their capabilities with SqlAgent.
sidebar:
  order: 4
---

SqlAgent supports all four database engines that Laravel supports. The agent can query your application data on any of these engines, and full-text search capabilities vary by engine.

## MySQL

Full support including:

- Full-text search with `MATCH ... AGAINST` (natural language and boolean mode)
- JSON column support for metadata storage
- Configurable search mode via `sql-agent.search.drivers.database.mysql.mode`

## PostgreSQL

Full support including:

- Full-text search with `tsvector` and `tsquery`
- Configurable text search language (default: `english`)
- JSONB column support for metadata storage

## SQLite

Supported with limitations:

- Full-text search falls back to `LIKE` queries (less accurate than native full-text search)
- JSON support depends on the SQLite version and compile flags
- Suitable for development, testing, and small datasets

## SQL Server

Supported with full-text search:

- Uses `CONTAINS` predicates for full-text queries
- Requires a full-text catalog to be configured on the relevant tables
