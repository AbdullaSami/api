<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            CoursesCategorySeeder::class,
            CoursesSeeder::class,
            SectionSeeder::class,
            LessonSeeder::class,
        ]);
    }
}
