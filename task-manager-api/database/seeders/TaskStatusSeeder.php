<?php

namespace Database\Seeders;

use App\Packages\Task\Statuses\Models\TaskStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaskStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            ['name' => 'Pending', 'slug' => 'pending'],
            ['name' => 'In Progress', 'slug' => 'in_progress'],
            ['name' => 'Done', 'slug' => 'done'],
        ];

        foreach ($statuses as $status) {
            TaskStatus::firstOrCreate(
                ['slug' => $status['slug']],
                ['id' => (string) Str::uuid(), 'name' => $status['name']]
            );
        }
    }
}
