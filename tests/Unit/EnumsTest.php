<?php

use Knobik\SqlAgent\Enums\BusinessRuleType;
use Knobik\SqlAgent\Enums\LearningCategory;
use Knobik\SqlAgent\Enums\MessageRole;

describe('BusinessRuleType', function () {
    it('has correct values', function () {
        expect(BusinessRuleType::Metric->value)->toBe('metric');
        expect(BusinessRuleType::Rule->value)->toBe('rule');
        expect(BusinessRuleType::Gotcha->value)->toBe('gotcha');
    });

    it('has labels', function () {
        expect(BusinessRuleType::Metric->label())->toBe('Metric');
        expect(BusinessRuleType::Rule->label())->toBe('Business Rule');
        expect(BusinessRuleType::Gotcha->label())->toBe('Gotcha');
    });

    it('has descriptions', function () {
        expect(BusinessRuleType::Metric->description())->toContain('metric');
        expect(BusinessRuleType::Rule->description())->toContain('business rule');
        expect(BusinessRuleType::Gotcha->description())->toContain('gotcha');
    });
});

describe('MessageRole', function () {
    it('has correct values', function () {
        expect(MessageRole::User->value)->toBe('user');
        expect(MessageRole::Assistant->value)->toBe('assistant');
        expect(MessageRole::System->value)->toBe('system');
        expect(MessageRole::Tool->value)->toBe('tool');
    });

    it('identifies user messages', function () {
        expect(MessageRole::User->isFromUser())->toBeTrue();
        expect(MessageRole::Assistant->isFromUser())->toBeFalse();
    });

    it('identifies assistant messages', function () {
        expect(MessageRole::Assistant->isFromAssistant())->toBeTrue();
        expect(MessageRole::User->isFromAssistant())->toBeFalse();
    });
});

describe('LearningCategory', function () {
    it('has correct values', function () {
        expect(LearningCategory::TypeError->value)->toBe('type_error');
        expect(LearningCategory::SchemaFix->value)->toBe('schema_fix');
        expect(LearningCategory::QueryPattern->value)->toBe('query_pattern');
        expect(LearningCategory::DataQuality->value)->toBe('data_quality');
        expect(LearningCategory::BusinessLogic->value)->toBe('business_logic');
    });

    it('has labels', function () {
        expect(LearningCategory::TypeError->label())->toBe('Type Error');
        expect(LearningCategory::SchemaFix->label())->toBe('Schema Fix');
    });
});
