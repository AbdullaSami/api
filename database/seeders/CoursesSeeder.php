<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CoursesCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = CoursesCategory::all();
        $levels = [1, 2, 3];

        $courseTemplates = [
            'Web Development' => [
                'Complete HTML & CSS Bootcamp',
                'JavaScript Fundamentals Masterclass',
                'React.js Complete Guide',
                'Vue.js from Scratch',
                'Angular Full Course',
                'Node.js Backend Development',
                'PHP & Laravel Mastery',
                'Python Django Development',
                'Full Stack JavaScript',
                'Modern Web Development Tools'
            ],
            'Mobile Development' => [
                'iOS Development with Swift',
                'Android Development with Kotlin',
                'React Native Complete Course',
                'Flutter Mobile Development',
                'Cross-Platform App Development',
                'Mobile App UI Design',
                'iOS Advanced Topics',
                'Android Performance Optimization',
                'Mobile Game Development',
                'App Store Optimization'
            ],
            'Data Science' => [
                'Python for Data Science',
                'Machine Learning Fundamentals',
                'Data Analysis with Pandas',
                'Deep Learning with TensorFlow',
                'Statistical Analysis Masterclass',
                'Data Visualization Techniques',
                'Big Data with Hadoop',
                'Natural Language Processing',
                'Computer Vision Basics',
                'Data Engineering Pipeline'
            ],
            'DevOps & Cloud' => [
                'AWS Cloud Practitioner',
                'Docker Containerization',
                'Kubernetes Orchestration',
                'CI/CD Pipeline Design',
                'Infrastructure as Code',
                'Cloud Security Fundamentals',
                'DevOps Culture & Practices',
                'Monitoring & Observability',
                'Serverless Architecture',
                'Multi-Cloud Strategies'
            ],
            'UI/UX Design' => [
                'Figma Complete Mastery',
                'User Research Methods',
                'Interaction Design Principles',
                'Design Thinking Workshop',
                'Prototyping Techniques',
                'Visual Design Fundamentals',
                'Mobile UI Design',
                'Web UX Best Practices',
                'Design Systems Creation',
                'Usability Testing Guide'
            ]
        ];

        foreach ($categories as $category) {
            $courses = $courseTemplates[$category->name] ?? [];

            foreach ($courses as $index => $courseTitle) {
                Course::create([
                    'title' => $courseTitle,
                    'category_id' => $category->id,
                    'description' => "Comprehensive course on {$courseTitle}. Learn from industry experts with hands-on projects and real-world applications.",
                    'thumbnail' => "courses/{$category->slug}-" . ($index + 1) . ".jpg",
                    'level' => $levels[array_rand($levels)],
                    'is_published' => true,
                    'instructor_id' => 1, // Assuming instructor with ID 1 exists
                    'duration' => rand(120, 480) // Duration in minutes
                ]);
            }
        }
    }
}
