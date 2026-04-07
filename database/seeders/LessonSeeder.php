<?php

namespace Database\Seeders;

use App\Models\Section;
use App\Models\Lesson;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sections = Section::all();

        $lessonTypes = [
            'Introduction',
            'Theory & Concepts',
            'Practical Demonstration',
            'Hands-on Exercise',
            'Best Practices',
            'Common Mistakes',
            'Advanced Techniques',
            'Real-world Example',
            'Troubleshooting',
            'Summary & Review',
            'Quiz & Assessment',
            'Project Work'
        ];

        foreach ($sections as $section) {
            // Randomly select 3-12 lessons for each section
            $lessonCount = rand(3, 12);

            for ($i = 0; $i < $lessonCount; $i++) {
                $lessonType = $lessonTypes[array_rand($lessonTypes)];

                $lesson = new Lesson([
                    'section_id' => $section->id,
                    'title' => "{$lessonType}: {$section->title}",
                    'description' => "Detailed {$lessonType} covering {$section->title} with practical examples and exercises.",
                    'video_id' => 'vid_' . uniqid(),
                    'duration' => rand(5, 45), // Duration in minutes
                    'order' => $i + 1,
                    'is_preview' => $i === 0 // Make first lesson of each section a preview
                ]);
                $lesson->save(); // This will trigger the slug generation
            }
        }
    }
}
