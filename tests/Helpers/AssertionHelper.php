<?php

namespace Tests\Helpers;

use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;

/**
 * Вспомогательный класс для дополнительных assertion методов
 */
class AssertionHelper
{
    /**
     * Проверить структуру JSON ответа для задачи
     */
    public static function assertTaskJsonStructure(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'description',
                'status',
                'priority',
                'user_id',
                'deadline',
                'created_at',
                'updated_at'
            ]
        ]);
    }

    /**
     * Проверить структуру JSON ответа для списка задач
     */
    public static function assertTaskListJsonStructure(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'description',
                    'status',
                    'priority',
                    'user_id',
                    'deadline',
                    'created_at',
                    'updated_at'
                ]
            ],
            'links' => [
                'first',
                'last',
                'prev',
                'next'
            ],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'per_page',
                'to',
                'total'
            ]
        ]);
    }

    /**
     * Проверить структуру JSON ответа для аутентификации
     */
    public static function assertAuthJsonStructure(TestResponse $response): void
    {
        $response->assertJsonStructure([
            'user' => [
                'id',
                'name',
                'email'
            ],
            'token'
        ]);
    }

    /**
     * Проверить, что ответ содержит ошибки валидации для указанных полей
     */
    public static function assertValidationErrors(TestResponse $response, array $fields): void
    {
        $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors'
                ]);

        foreach ($fields as $field) {
            $response->assertJsonValidationErrors($field);
        }
    }

    /**
     * Проверить успешный JSON ответ с данными
     */
    public static function assertSuccessfulResponse(TestResponse $response, int $statusCode = 200): void
    {
        $response->assertStatus($statusCode)
                ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Проверить, что задача принадлежит пользователю
     */
    public static function assertTaskBelongsToUser(array $taskData, int $userId): void
    {
        Assert::assertEquals($userId, $taskData['user_id'], 'Task does not belong to the expected user');
    }

    /**
     * Проверить, что все задачи в списке принадлежат пользователю
     */
    public static function assertAllTasksBelongToUser(array $tasks, int $userId): void
    {
        foreach ($tasks as $task) {
            self::assertTaskBelongsToUser($task, $userId);
        }
    }

    /**
     * Проверить структуру данных аналитики
     */
    public static function assertAnalyticsStructure(array $data, array $expectedKeys): void
    {
        foreach ($expectedKeys as $key) {
            Assert::assertArrayHasKey($key, $data, "Analytics data missing key: {$key}");
        }
    }

    /**
     * Проверить статистику завершения задач
     */
    public static function assertCompletionStats(array $stats, int $expectedCompleted, int $expectedNotCompleted): void
    {
        Assert::assertCount(2, $stats, 'Completion stats should have exactly 2 items');
        
        $completedStat = collect($stats)->firstWhere('status', 'Выполненные');
        $notCompletedStat = collect($stats)->firstWhere('status', 'Невыполненные');
        
        Assert::assertNotNull($completedStat, 'Completed tasks stat not found');
        Assert::assertNotNull($notCompletedStat, 'Not completed tasks stat not found');
        
        Assert::assertEquals($expectedCompleted, $completedStat['count'], 'Completed tasks count mismatch');
        Assert::assertEquals($expectedNotCompleted, $notCompletedStat['count'], 'Not completed tasks count mismatch');
    }

    /**
     * Проверить статистику по приоритетам
     */
    public static function assertPriorityStats(array $stats, array $expectedCounts): void
    {
        $priorityLabels = [
            1 => 'Высокий',
            2 => 'Средний',
            3 => 'Низкий'
        ];

        foreach ($expectedCounts as $priority => $expectedCount) {
            $label = $priorityLabels[$priority];
            $stat = collect($stats)->firstWhere('priority', $label);
            
            Assert::assertNotNull($stat, "Priority stat for '{$label}' not found");
            Assert::assertEquals($expectedCount, $stat['count'], "Priority '{$label}' count mismatch");
        }
    }

    /**
     * Проверить общую статистику пользователя
     */
    public static function assertOverallStats(array $stats, array $expected): void
    {
        $requiredKeys = [
            'total_tasks',
            'completed_tasks',
            'in_progress_tasks',
            'todo_tasks',
            'completion_rate',
            'completed_last_30_days'
        ];

        foreach ($requiredKeys as $key) {
            Assert::assertArrayHasKey($key, $stats, "Overall stats missing key: {$key}");
        }

        foreach ($expected as $key => $value) {
            Assert::assertEquals($value, $stats[$key], "Overall stats '{$key}' mismatch");
        }
    }

    /**
     * Проверить, что время выполнения не превышает лимит
     */
    public static function assertExecutionTime(float $startTime, float $maxSeconds = 1.0): void
    {
        $executionTime = microtime(true) - $startTime;
        Assert::assertLessThan($maxSeconds, $executionTime, "Execution time {$executionTime}s exceeds limit {$maxSeconds}s");
    }

    /**
     * Проверить пагинацию в ответе
     */
    public static function assertPagination(TestResponse $response, int $expectedTotal, int $expectedPerPage): void
    {
        $meta = $response->json('meta');
        
        Assert::assertArrayHasKey('total', $meta, 'Pagination meta missing total');
        Assert::assertArrayHasKey('per_page', $meta, 'Pagination meta missing per_page');
        Assert::assertArrayHasKey('current_page', $meta, 'Pagination meta missing current_page');
        Assert::assertArrayHasKey('last_page', $meta, 'Pagination meta missing last_page');
        
        Assert::assertEquals($expectedTotal, $meta['total'], 'Total items count mismatch');
        Assert::assertEquals($expectedPerPage, $meta['per_page'], 'Per page count mismatch');
    }

    /**
     * Проверить, что ответ содержит правильные HTTP заголовки для API
     */
    public static function assertApiHeaders(TestResponse $response): void
    {
        $response->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Проверить, что задача имеет корректные значения по умолчанию
     */
    public static function assertTaskDefaults(array $taskData): void
    {
        if (!isset($taskData['status'])) {
            Assert::assertEquals('todo', $taskData['status'], 'Default status should be todo');
        }
        
        if (!isset($taskData['priority'])) {
            Assert::assertEquals(2, $taskData['priority'], 'Default priority should be 2 (medium)');
        }
    }

    /**
     * Проверить, что задача не содержит чувствительных данных
     */
    public static function assertNoSensitiveData(array $taskData): void
    {
        $sensitiveFields = ['password', 'remember_token', 'api_token'];
        
        foreach ($sensitiveFields as $field) {
            Assert::assertArrayNotHasKey($field, $taskData, "Task data should not contain sensitive field: {$field}");
        }
    }

    /**
     * Проверить формат даты в ответе
     */
    public static function assertDateFormat(string $date, string $format = 'Y-m-d\TH:i:s.u\Z'): void
    {
        $parsedDate = \DateTime::createFromFormat($format, $date);
        Assert::assertNotFalse($parsedDate, "Date '{$date}' does not match expected format '{$format}'");
    }

    /**
     * Проверить, что массив отсортирован по указанному полю
     */
    public static function assertArraySorted(array $items, string $field, string $direction = 'asc'): void
    {
        $values = array_column($items, $field);
        $sorted = $values;
        
        if ($direction === 'asc') {
            sort($sorted);
        } else {
            rsort($sorted);
        }
        
        Assert::assertEquals($sorted, $values, "Array is not sorted by '{$field}' in '{$direction}' order");
    }
}