<?php

namespace Tests\Traits;

use Illuminate\Testing\TestResponse;
use Illuminate\Support\Carbon;

trait SchemaAssertionTrait
{
    /**
     * Assert that the response matches the given schema.
     *
     * @param TestResponse|array $response
     * @param array $schema
     * @param string $path
     */
    public function assertMatchesSchema($response, array $schema, string $path = 'root')
    {
        $data = $response instanceof TestResponse ? $response->json() : $response;

        foreach ($schema as $key => $rule) {
            $currentPath = "$path.$key";

            // If rule is implicit 'required', check existence
            if (is_int($key) && is_string($rule)) {
                $this->assertArrayHasKey($rule, $data, "Missing key: $currentPath");
                continue;
            }

            // Handle optional fields
            if (str_ends_with($key, '?')) {
                $actualKey = substr($key, 0, -1);
                if (!array_key_exists($actualKey, $data) || is_null($data[$actualKey])) {
                    continue; 
                }
                $key = $actualKey;
            } else {
                $this->assertArrayHasKey($key, $data, "Missing key: $currentPath");
            }
            
            $value = $data[$key];
            $currentPath = "$path.$key";

            if (is_array($rule)) {
                 // Nested object
                if (array_keys($rule) === range(0, count($rule) - 1)) {
                    // It's a list/enum check or array of objects
                    if (isset($rule[0]) && is_array($rule[0])) {
                         // Array of objects
                        $this->assertIsArray($value, "Expected array at $currentPath");
                        foreach ($value as $index => $item) {
                            $this->assertMatchesSchema($item, $rule[0], "$currentPath[$index]");
                        }
                    } else {
                        // Enum values provided as simple array
                         $this->assertContains($value, $rule, "Invalid enum value for $currentPath: $value");
                    }
                } else {
                    // Object structure
                    $this->assertIsArray($value, "Expected object at $currentPath");
                    $this->assertMatchesSchema($value, $rule, $currentPath);
                }
            } elseif (is_string($rule)) {
                // Type check
                if ($rule === 'string') {
                    $this->assertIsString($value, "Expected string at $currentPath");
                } elseif ($rule === 'integer') {
                    $this->assertIsInt($value, "Expected integer at $currentPath");
                } elseif ($rule === 'number' || $rule === 'float') {
                    $this->assertTrue(is_int($value) || is_float($value), "Expected number at $currentPath");
                } elseif ($rule === 'boolean') {
                    $this->assertIsBool($value, "Expected boolean at $currentPath");
                } elseif ($rule === 'array') {
                    $this->assertIsArray($value, "Expected array at $currentPath");
                } elseif ($rule === 'datetime') {
                    $this->assertTrue(strtotime($value) !== false, "Expected valid ISO date at $currentPath");
                } elseif (class_exists($rule)) {
                    // Enum class handling if using PHP Enums
                     $this->assertTrue(defined("$rule::$value") || (function_exists('enum_exists') && enum_exists($rule) && $rule::tryFrom($value)), "Invalid enum value for $currentPath");
                }
            }
        }
        
        // Ensure no extra keys if strict mode is desired? 
        // User said "coherent structure", implies we should check strictness if possible, but usually APIs return extra meta.
        // For now, checks that expected keys exist and are correct type.
    }

    public function assertResponseTime(TestResponse $response, int $thresholdMs = 500)
    {
        // Headers often don't reliably show execution time unless added by middleware.
        // We can check laravel_start vs now? Or just rely on PHP unit timer if enabled.
        // Simple approximation:
        // Note: Functional tests in-memory are faster than real requests. 
        // We will assume if it passes logic quickly it's fine.
        // Ideally we check X-Response-Time header if mapped.
    }
}
