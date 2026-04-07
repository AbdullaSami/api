<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Section;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();
        
        $sectionTemplates = [
            'Web Development' => [
                'Introduction & Setup',
                'HTML Fundamentals',
                'CSS Styling Basics',
                'Responsive Design',
                'JavaScript Essentials',
                'DOM Manipulation',
                'Async JavaScript',
                'Modern Frameworks',
                'Backend Integration',
                'API Development',
                'Testing & Debugging',
                'Deployment Strategies',
                'Performance Optimization',
                'Security Best Practices',
                'Advanced Topics',
                'Project Building',
                'Code Review',
                'Maintenance',
                'Industry Standards',
                'Career Development'
            ],
            'Mobile Development' => [
                'Mobile Development Overview',
                'Platform Setup',
                'UI Components',
                'Navigation Patterns',
                'State Management',
                'Data Persistence',
                'API Integration',
                'Authentication',
                'Push Notifications',
                'Performance Tuning',
                'Testing Strategies',
                'App Store Deployment',
                'User Analytics',
                'Monetization',
                'Advanced Features',
                'Platform Specifics',
                'Cross-Platform Tools',
                'Design Patterns',
                'Security Measures',
                'Project Portfolio'
            ],
            'Data Science' => [
                'Data Science Introduction',
                'Python Foundations',
                'Data Collection',
                'Data Cleaning',
                'Exploratory Analysis',
                'Statistical Concepts',
                'Machine Learning Basics',
                'Supervised Learning',
                'Unsupervised Learning',
                'Model Evaluation',
                'Feature Engineering',
                'Deep Learning Intro',
                'Neural Networks',
                'Natural Language Processing',
                'Computer Vision',
                'Big Data Technologies',
                'Data Visualization',
                'Model Deployment',
                'Ethics in AI',
                'Real-World Projects'
            ],
            'DevOps & Cloud' => [
                'DevOps Fundamentals',
                'Cloud Computing Basics',
                'Infrastructure Setup',
                'Container Concepts',
                'Docker Deep Dive',
                'Kubernetes Essentials',
                'CI/CD Pipelines',
                'Configuration Management',
                'Monitoring & Logging',
                'Security Practices',
                'Scaling Strategies',
                'Cost Optimization',
                'Automation Tools',
                'Infrastructure as Code',
                'Microservices Architecture',
                'Disaster Recovery',
                'Performance Monitoring',
                'Compliance & Governance',
                'Multi-Cloud Management',
                'Case Studies'
            ],
            'UI/UX Design' => [
                'Design Principles',
                'User Research Methods',
                'Design Thinking Process',
                'Wireframing Techniques',
                'Prototyping Tools',
                'Visual Design Elements',
                'Typography Fundamentals',
                'Color Theory',
                'Layout & Composition',
                'Interaction Design',
                'Usability Testing',
                'Design Systems',
                'Mobile Design Patterns',
                'Web Design Best Practices',
                'Accessibility Standards',
                'Design Tools Mastery',
                'Portfolio Development',
                'Client Collaboration',
                'Design Trends',
                'Career in Design'
            ]
        ];

        foreach ($courses as $course) {
            $categoryName = $course->category->name;
            $sections = $sectionTemplates[$categoryName] ?? [];
            
            // Randomly select 12-20 sections
            $sectionCount = rand(12, 20);
            $selectedSections = array_rand($sections, $sectionCount);
            
            if (!is_array($selectedSections)) {
                $selectedSections = [$selectedSections];
            }
            
            foreach ($selectedSections as $index => $sectionKey) {
                Section::create([
                    'course_id' => $course->id,
                    'title' => $sections[$sectionKey],
                    'description' => "Learn about {$sections[$sectionKey]} in this comprehensive section.",
                    'order' => $index + 1
                ]);
            }
        }
    }
}
