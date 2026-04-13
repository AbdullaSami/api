<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSubcategory;
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
        $subcategories = CourseSubcategory::all();
        $levels = [1, 2, 3];

        $courseTemplates = [
            'Frontend Development' => [
                'Complete HTML & CSS Bootcamp',
                'JavaScript Fundamentals Masterclass',
                'React.js Complete Guide',
                'Vue.js from Scratch',
                'Angular Full Course',
            ],
            'Backend Development' => [
                'Node.js Backend Development',
                'PHP & Laravel Mastery',
                'Python Django Development',
                'Express.js Framework',
                'REST API Development',
            ],
            'Full Stack Development' => [
                'Full Stack JavaScript',
                'Modern Web Development Tools',
                'MERN Stack Complete',
                'MEAN Stack Mastery',
                'Python Full Stack',
            ],
            'iOS Development' => [
                'iOS Development with Swift',
                'SwiftUI Fundamentals',
                'iOS Advanced Topics',
                'iOS App Architecture',
                'Core Data & Persistence',
            ],
            'Android Development' => [
                'Android Development with Kotlin',
                'Android Performance Optimization',
                'Jetpack Compose',
                'Android Architecture',
                'Kotlin Coroutines',
            ],
            'Cross-Platform Development' => [
                'React Native Complete Course',
                'Flutter Mobile Development',
                'Cross-Platform App Development',
                'Mobile App UI Design',
                'App Store Optimization',
            ],
            'Machine Learning' => [
                'Python for Data Science',
                'Machine Learning Fundamentals',
                'Deep Learning with TensorFlow',
                'Neural Networks',
                'ML Model Deployment',
            ],
            'Data Analysis' => [
                'Data Analysis with Pandas',
                'Statistical Analysis Masterclass',
                'Data Visualization Techniques',
                'Excel for Data Analysis',
                'SQL for Data Analysis',
            ],
            'Deep Learning' => [
                'Big Data with Hadoop',
                'Natural Language Processing',
                'Computer Vision Basics',
                'Data Engineering Pipeline',
                'AI Ethics & Governance',
            ],
            'Cloud Computing' => [
                'AWS Cloud Practitioner',
                'Cloud Security Fundamentals',
                'Serverless Architecture',
                'Multi-Cloud Strategies',
                'Cloud Cost Management',
            ],
            'DevOps Tools' => [
                'Docker Containerization',
                'Kubernetes Orchestration',
                'CI/CD Pipeline Design',
                'DevOps Culture & Practices',
                'Monitoring & Observability',
            ],
            'Infrastructure as Code' => [
                'Infrastructure as Code',
                'Terraform Fundamentals',
                'Ansible Automation',
                'Configuration Management',
                'GitOps Workflow',
            ],
            'UI Design' => [
                'Figma Complete Mastery',
                'Interaction Design Principles',
                'Prototyping Techniques',
                'Visual Design Fundamentals',
                'Mobile UI Design',
            ],
            'UX Research' => [
                'User Research Methods',
                'Design Thinking Workshop',
                'Web UX Best Practices',
                'Usability Testing Guide',
                'User Journey Mapping',
            ],
            'Design Systems' => [
                'Design Systems Creation',
                'Component Libraries',
                'Design Tokens',
                'Pattern Libraries',
                'Design Documentation',
            ],
        ];

        foreach ($subcategories as $subcategory) {
            $courses = $courseTemplates[$subcategory->name] ?? [];

            foreach ($courses as $index => $courseTitle) {
                Course::create([
                    'title' => $courseTitle,
                    'sub_category_id' => $subcategory->id,
                    'description' => "Comprehensive course on {$courseTitle}. Learn from industry experts with hands-on projects and real-world applications.",
                    'thumbnail' => "courses/{$subcategory->slug}-" . ($index + 1) . ".jpg",
                    'level' => $levels[array_rand($levels)],
                    'is_published' => true,
                    'instructor_id' => 1, // Assuming instructor with ID 1 exists
                    'duration' => rand(120, 480) // Duration in minutes
                ]);
            }
        }
    }
}
