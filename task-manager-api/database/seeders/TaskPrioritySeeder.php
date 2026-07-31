<?php

namespace Database\Seeders;

use App\Packages\Task\Priorities\Models\TaskPriority;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaskPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $priorities = [
            ['name' => 'Low', 'slug' => 'low', 'order' => 1],
            ['name' => 'Medium', 'slug' => 'medium', 'order' => 2],
            ['name' => 'High', 'slug' => 'high', 'order' => 3],
            ['name' => 'Urgent', 'slug' => 'urgent', 'order' => 4],
        ];

        foreach ($priorities as $priority) {
            TaskPriority::firstOrCreate(
                ['slug' => $priority['slug']],
                ['id' => (string) Str::uuid(), 'name' => $priority['name'], 'order' => $priority['order']]
            );
        }
    }
}
