<?php

namespace Database\Seeders;

use App\Models\CourseSubcategory;
use App\Models\CoursesCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CourseSubcategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = CoursesCategory::all();

        $subcategoriesByCategory = [
            'Web Development' => [
                [
                    'name' => 'Frontend Development',
                    'description' => 'Learn HTML, CSS, JavaScript, and modern frontend frameworks like React, Vue, and Angular.',
                    'image' => 'subcategories/frontend-development.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Backend Development',
                    'description' => 'Master server-side programming with Node.js, PHP, Python, and database management.',
                    'image' => 'subcategories/backend-development.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Full Stack Development',
                    'description' => 'Complete web development covering both frontend and backend technologies.',
                    'image' => 'subcategories/full-stack-development.jpg',
                    'status' => true,
                ],
            ],
            'Mobile Development' => [
                [
                    'name' => 'iOS Development',
                    'description' => 'Build native iOS applications using Swift and SwiftUI.',
                    'image' => 'subcategories/ios-development.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Android Development',
                    'description' => 'Create Android apps using Kotlin and Jetpack Compose.',
                    'image' => 'subcategories/android-development.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Cross-Platform Development',
                    'description' => 'Build mobile apps for both iOS and Android using React Native and Flutter.',
                    'image' => 'subcategories/cross-platform-development.jpg',
                    'status' => true,
                ],
            ],
            'Data Science' => [
                [
                    'name' => 'Machine Learning',
                    'description' => 'Learn ML algorithms, model training, and predictive analytics.',
                    'image' => 'subcategories/machine-learning.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Data Analysis',
                    'description' => 'Master data manipulation, visualization, and analysis with Python and R.',
                    'image' => 'subcategories/data-analysis.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Deep Learning',
                    'description' => 'Advanced neural networks, TensorFlow, and AI model development.',
                    'image' => 'subcategories/deep-learning.jpg',
                    'status' => true,
                ],
            ],
            'DevOps & Cloud' => [
                [
                    'name' => 'Cloud Computing',
                    'description' => 'AWS, Azure, and Google Cloud platform services and architecture.',
                    'image' => 'subcategories/cloud-computing.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'DevOps Tools',
                    'description' => 'Docker, Kubernetes, CI/CD pipelines, and infrastructure automation.',
                    'image' => 'subcategories/devops-tools.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Infrastructure as Code',
                    'description' => 'Terraform, Ansible, and modern infrastructure management.',
                    'image' => 'subcategories/infrastructure-as-code.jpg',
                    'status' => true,
                ],
            ],
            'UI/UX Design' => [
                [
                    'name' => 'UI Design',
                    'description' => 'Visual design principles, color theory, and interface design with Figma.',
                    'image' => 'subcategories/ui-design.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'UX Research',
                    'description' => 'User research methods, usability testing, and user experience optimization.',
                    'image' => 'subcategories/ux-research.jpg',
                    'status' => true,
                ],
                [
                    'name' => 'Design Systems',
                    'description' => 'Create scalable design systems and component libraries.',
                    'image' => 'subcategories/design-systems.jpg',
                    'status' => true,
                ],
            ],
        ];

        foreach ($categories as $category) {
            $subcategories = $subcategoriesByCategory[$category->name] ?? [];

            foreach ($subcategories as $subcategory) {
                CourseSubcategory::create(array_merge($subcategory, [
                    'category_id' => $category->id,
                ]));
            }
        }
    }
}
