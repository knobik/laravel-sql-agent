<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Knobik\SqlAgent\Enums\BusinessRuleType;
use Knobik\SqlAgent\Models\BusinessRule;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->artisan('migrate');
});

describe('BusinessRule', function () {
    it('can be created with metric type', function () {
        $rule = BusinessRule::create([
            'type' => BusinessRuleType::Metric,
            'name' => 'Active User',
            'description' => 'User logged in within 30 days',
            'conditions' => ['calculation' => 'WHERE last_login > NOW() - INTERVAL 30 DAY'],
        ]);

        expect($rule->type)->toBe(BusinessRuleType::Metric);
        expect($rule->isMetric())->toBeTrue();
        expect($rule->isRule())->toBeFalse();
        expect($rule->isGotcha())->toBeFalse();
    });

    it('scopes by type', function () {
        BusinessRule::create(['type' => BusinessRuleType::Metric, 'name' => 'M1', 'description' => 'D1']);
        BusinessRule::create(['type' => BusinessRuleType::Rule, 'name' => 'R1', 'description' => 'D2']);
        BusinessRule::create(['type' => BusinessRuleType::Gotcha, 'name' => 'G1', 'description' => 'D3']);

        expect(BusinessRule::metrics()->count())->toBe(1);
        expect(BusinessRule::rules()->count())->toBe(1);
        expect(BusinessRule::gotchas()->count())->toBe(1);
    });

    it('can get tables affected', function () {
        $rule = BusinessRule::create([
            'type' => BusinessRuleType::Gotcha,
            'name' => 'Soft deletes',
            'description' => 'Check deleted_at',
            'conditions' => ['tables_affected' => ['users', 'posts']],
        ]);

        expect($rule->getTablesAffected())->toBe(['users', 'posts']);
    });
});
