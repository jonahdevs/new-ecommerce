<?php

use App\Concerns\LogsModelChanges;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

/**
 * Unit tests for LogsModelChanges trait
 * 
 * **Validates: Requirements 2.1, 2.2, 2.3**
 * 
 * Tests the LogsModelChanges trait configuration methods:
 * - getLoggedAttributes() returns correct field array
 * - getLogName() returns correct log category
 * - getActivitylogOptions() configures correct options
 */

// Create a concrete test model that uses the trait
class TestModel extends Model
{
    use LogsModelChanges;

    protected $table = 'test_models';

    protected function getLoggedAttributes(): array
    {
        return ['name', 'email', 'status'];
    }
}

// Create another test model with custom log name
class CustomLogNameModel extends Model
{
    use LogsModelChanges;

    protected $table = 'custom_models';

    protected function getLoggedAttributes(): array
    {
        return ['title', 'description'];
    }

    protected function getLogName(): string
    {
        return 'custom_category';
    }
}

test('getLoggedAttributes returns correct field array', function () {
    // Use reflection to access protected method without database connection
    $reflection = new ReflectionClass(TestModel::class);
    $method = $reflection->getMethod('getLoggedAttributes');
    $method->setAccessible(true);

    $model = $reflection->newInstanceWithoutConstructor();
    $attributes = $method->invoke($model);

    expect($attributes)->toBeArray()
        ->and($attributes)->toBe(['name', 'email', 'status'])
        ->and($attributes)->toHaveCount(3);
});

test('getLogName returns correct log category using default implementation', function () {
    // Use reflection to access protected method without database connection
    $reflection = new ReflectionClass(TestModel::class);
    $method = $reflection->getMethod('getLogName');
    $method->setAccessible(true);

    $model = $reflection->newInstanceWithoutConstructor();
    $logName = $method->invoke($model);

    expect($logName)->toBe('testmodel')
        ->and($logName)->toBeString();
});

test('getLogName returns custom log category when overridden', function () {
    // Use reflection to access protected method without database connection
    $reflection = new ReflectionClass(CustomLogNameModel::class);
    $method = $reflection->getMethod('getLogName');
    $method->setAccessible(true);

    $model = $reflection->newInstanceWithoutConstructor();
    $logName = $method->invoke($model);

    expect($logName)->toBe('custom_category');
});

test('getActivitylogOptions configures correct options', function () {
    // Use reflection to create model instance without database connection
    $reflection = new ReflectionClass(TestModel::class);
    $model = $reflection->newInstanceWithoutConstructor();

    $options = $model->getActivitylogOptions();

    // Verify it returns LogOptions instance
    expect($options)->toBeInstanceOf(LogOptions::class);

    // Verify logOnly is configured with the correct attributes
    expect($options->logAttributes)->toBe(['name', 'email', 'status']);
});

test('getActivitylogOptions configures logOnlyDirty option', function () {
    // Use reflection to create model instance without database connection
    $reflection = new ReflectionClass(TestModel::class);
    $model = $reflection->newInstanceWithoutConstructor();

    $options = $model->getActivitylogOptions();

    // Verify logOnlyDirty is enabled
    expect($options->logOnlyDirty)->toBeTrue();
});

test('getActivitylogOptions configures dontSubmitEmptyLogs option', function () {
    // Use reflection to create model instance without database connection
    $reflection = new ReflectionClass(TestModel::class);
    $model = $reflection->newInstanceWithoutConstructor();

    $options = $model->getActivitylogOptions();

    // Verify submitEmptyLogs is disabled (dontSubmitEmptyLogs was called)
    expect($options->submitEmptyLogs)->toBeFalse();
});

test('getActivitylogOptions uses correct log name', function () {
    // Use reflection to create model instance without database connection
    $reflection = new ReflectionClass(TestModel::class);
    $model = $reflection->newInstanceWithoutConstructor();

    $options = $model->getActivitylogOptions();

    // Verify the log name is set correctly
    expect($options->logName)->toBe('testmodel');
});

test('getActivitylogOptions uses custom log name when overridden', function () {
    // Use reflection to create model instance without database connection
    $reflection = new ReflectionClass(CustomLogNameModel::class);
    $model = $reflection->newInstanceWithoutConstructor();

    $options = $model->getActivitylogOptions();

    // Verify the custom log name is used
    expect($options->logName)->toBe('custom_category');
});

test('trait can be applied to multiple models independently', function () {
    // Use reflection to create model instances without database connection
    $reflection1 = new ReflectionClass(TestModel::class);
    $model1 = $reflection1->newInstanceWithoutConstructor();

    $reflection2 = new ReflectionClass(CustomLogNameModel::class);
    $model2 = $reflection2->newInstanceWithoutConstructor();

    // Access protected methods
    $method1 = $reflection1->getMethod('getLoggedAttributes');
    $method1->setAccessible(true);
    $method2 = $reflection2->getMethod('getLoggedAttributes');
    $method2->setAccessible(true);

    $logNameMethod1 = $reflection1->getMethod('getLogName');
    $logNameMethod1->setAccessible(true);
    $logNameMethod2 = $reflection2->getMethod('getLogName');
    $logNameMethod2->setAccessible(true);

    // Each model should have its own configuration
    expect($method1->invoke($model1))->toBe(['name', 'email', 'status'])
        ->and($method2->invoke($model2))->toBe(['title', 'description'])
        ->and($logNameMethod1->invoke($model1))->toBe('testmodel')
        ->and($logNameMethod2->invoke($model2))->toBe('custom_category');
});
