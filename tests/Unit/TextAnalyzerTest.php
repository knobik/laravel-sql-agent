<?php

use Knobik\SqlAgent\Support\TextAnalyzer;

describe('extractKeywords', function () {
    test('extracts meaningful words from text', function () {
        $keywords = TextAnalyzer::extractKeywords('Show me the total revenue for each customer');

        expect($keywords)->toContain('total', 'revenue', 'customer');
        expect($keywords)->not->toContain('show', 'me', 'the', 'for');
    });

    test('filters out short words', function () {
        $keywords = TextAnalyzer::extractKeywords('go to it');

        expect($keywords)->toBeEmpty();
    });

    test('handles empty text', function () {
        expect(TextAnalyzer::extractKeywords(''))->toBeEmpty();
    });

    test('converts to lowercase', function () {
        $keywords = TextAnalyzer::extractKeywords('REVENUE Customer');

        expect($keywords)->toBe(['revenue', 'customer']);
    });

    test('splits on non-alphanumeric characters', function () {
        $keywords = TextAnalyzer::extractKeywords('order_total per-customer');

        expect($keywords)->toContain('order', 'total', 'per', 'customer');
    });
});

describe('extractTablesFromSql', function () {
    test('extracts tables from FROM clause', function () {
        $tables = TextAnalyzer::extractTablesFromSql('SELECT * FROM users WHERE active = 1');

        expect($tables)->toBe(['users']);
    });

    test('extracts tables from JOIN clause', function () {
        $tables = TextAnalyzer::extractTablesFromSql('SELECT * FROM orders JOIN customers ON orders.customer_id = customers.id');

        expect($tables)->toContain('orders', 'customers');
    });

    test('extracts tables from UPDATE clause', function () {
        $tables = TextAnalyzer::extractTablesFromSql('UPDATE users SET active = 0');

        expect($tables)->toBe(['users']);
    });

    test('extracts tables from INTO clause', function () {
        $tables = TextAnalyzer::extractTablesFromSql('INSERT INTO orders (user_id) VALUES (1)');

        expect($tables)->toBe(['orders']);
    });

    test('handles quoted table names', function () {
        $tables = TextAnalyzer::extractTablesFromSql('SELECT * FROM `users` JOIN "orders" ON `users`.id = "orders".user_id');

        expect($tables)->toContain('users', 'orders');
    });

    test('deduplicates table names', function () {
        $tables = TextAnalyzer::extractTablesFromSql('SELECT * FROM users JOIN users ON users.id = users.id');

        expect($tables)->toBe(['users']);
    });

    test('handles complex queries with multiple joins', function () {
        $sql = 'SELECT o.id FROM orders o JOIN customers c ON o.customer_id = c.id LEFT JOIN products p ON o.product_id = p.id';
        $tables = TextAnalyzer::extractTablesFromSql($sql);

        expect($tables)->toContain('orders', 'customers', 'products');
    });
});

describe('prepareSearchTerm', function () {
    test('returns keywords joined by spaces', function () {
        $term = TextAnalyzer::prepareSearchTerm('Show me the total revenue');

        expect($term)->toBe('total revenue');
    });

    test('returns empty string for stop-words-only input', function () {
        expect(TextAnalyzer::prepareSearchTerm('show me the'))->toBe('');
    });
});
