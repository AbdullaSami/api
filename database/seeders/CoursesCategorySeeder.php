<?php

namespace Database\Seeders;

use App\Models\CoursesCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CoursesCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Development',
                'description' => 'Learn modern web development technologies including HTML, CSS, JavaScript, and popular frameworks like React, Vue, and Angular.',
                'image' => 'categories/web-development.jpg',
                'status' => true,
            ],
            [
                'name' => 'Mobile Development',
                'description' => 'Master mobile app development for iOS and Android using native and cross-platform technologies like React Native and Flutter.',
                'image' => 'categories/mobile-development.jpg',
                'status' => true,
            ],
            [
                'name' => 'Data Science',
                'description' => 'Explore data analysis, machine learning, and artificial intelligence with Python, R, and modern data science tools.',
                'image' => 'categories/data-science.jpg',
                'status' => true,
            ],
            [
                'name' => 'DevOps & Cloud',
                'description' => 'Learn cloud computing, containerization, CI/CD pipelines, and infrastructure as code with AWS, Docker, and Kubernetes.',
                'image' => 'categories/devops-cloud.jpg',
                'status' => true,
            ],
            [
                'name' => 'UI/UX Design',
                'description' => 'Master user interface and user experience design principles, tools like Figma and Adobe XD, and design thinking methodologies.',
                'image' => 'categories/ui-ux-design.jpg',
                'status' => true,
            ],
        ];

        foreach ($categories as $category) {
            CoursesCategory::create($category);
        }
    }
}
