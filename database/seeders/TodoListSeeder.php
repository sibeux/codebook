<?php

namespace Database\Seeders;

use App\Models\TodoList;
use App\Models\TodoProject;
use App\Models\User;
use Illuminate\Database\Seeder;

class TodoListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->each(function (User $user): void {
            TodoProject::where('user_id', $user->user_id)
                ->each(function (TodoProject $project) use ($user): void {
                    TodoList::ensureSystemLists($user, $project);
                });
        });
    }
}
