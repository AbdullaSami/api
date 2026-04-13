<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Skill;
class SkillsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = Course::all();

        // Define skill mappings based on course categories
        $skillMappings = [
            'Frontend Development' => [
                'HTML5', 'CSS3', 'JavaScript', 'Responsive Design', 'DOM Manipulation',
                'React.js', 'Vue.js', 'Angular', 'TypeScript', 'State Management',
                'Component Architecture', 'Frontend Testing', 'Webpack', 'Babel'
            ],
            'Backend Development' => [
                'Node.js', 'Express.js', 'PHP', 'Laravel', 'Python', 'Django',
                'REST APIs', 'API Design', 'Authentication', 'Database Design',
                'Server-Side Logic', 'Middleware', 'API Security', 'Microservices'
            ],
            'Full Stack Development' => [
                'Full Stack JavaScript', 'MERN Stack', 'MEAN Stack', 'Database Integration',
                'API Development', 'Frontend & Backend Integration', 'Version Control',
                'Deployment', 'System Design', 'Web Architecture', 'Authentication'
            ],
            'iOS Development' => [
                'Swift', 'SwiftUI', 'UIKit', 'iOS SDK', 'Xcode', 'Auto Layout',
                'Core Data', 'Networking', 'iOS Patterns', 'App Store Submission',
                'iOS Testing', 'Memory Management', 'Concurrency'
            ],
            'Android Development' => [
                'Kotlin', 'Java', 'Android SDK', 'Jetpack Compose', 'Gradle',
                'Android Architecture', 'Room Database', 'Retrofit', 'Coroutines',
                'Material Design', 'Android Testing', 'Performance Optimization'
            ],
            'Cross-Platform Development' => [
                'React Native', 'Flutter', 'Dart', 'Mobile UI', 'Cross-Platform APIs',
                'State Management', 'Mobile Testing', 'App Deployment', 'Platform-Specific Code',
                'Mobile Performance', 'Push Notifications'
            ],
            'Machine Learning' => [
                'Python', 'Machine Learning', 'Deep Learning', 'TensorFlow', 'PyTorch',
                'Neural Networks', 'Data Preprocessing', 'Model Training', 'Feature Engineering',
                'ML Algorithms', 'Model Evaluation', 'Scikit-learn'
            ],
            'Data Analysis' => [
                'Python', 'Pandas', 'NumPy', 'Data Analysis', 'Statistical Analysis',
                'Data Visualization', 'SQL', 'Excel', 'Data Cleaning', 'Exploratory Data Analysis',
                'Statistical Modeling', 'Data Interpretation'
            ],
            'Deep Learning' => [
                'Deep Learning', 'Neural Networks', 'TensorFlow', 'PyTorch', 'Computer Vision',
                'Natural Language Processing', 'Big Data', 'Hadoop', 'Spark', 'Data Engineering',
                'Model Deployment', 'AI Ethics'
            ],
            'Cloud Computing' => [
                'AWS', 'Azure', 'Google Cloud', 'Cloud Architecture', 'Cloud Security',
                'Serverless', 'Cloud Storage', 'Load Balancing', 'CDN', 'Cloud Monitoring',
                'Cost Optimization', 'Multi-Cloud'
            ],
            'DevOps Tools' => [
                'Docker', 'Kubernetes', 'CI/CD', 'Jenkins', 'GitLab CI', 'GitHub Actions',
                'Infrastructure', 'Monitoring', 'Logging', 'Configuration Management',
                'Container Orchestration', 'DevOps Practices'
            ],
            'Infrastructure as Code' => [
                'Terraform', 'Ansible', 'CloudFormation', 'Infrastructure as Code',
                'Configuration Management', 'GitOps', 'Puppet', 'Chef', 'Automation',
                'Version Control for Infrastructure', 'Deployment Automation'
            ],
            'UI Design' => [
                'Figma', 'Sketch', 'Adobe XD', 'UI Design', 'Visual Design',
                'Design Systems', 'Typography', 'Color Theory', 'Layout Design',
                'Prototyping', 'Design Principles', 'Mobile UI'
            ],
            'UX Research' => [
                'User Research', 'UX Design', 'Design Thinking', 'Usability Testing',
                'User Interviews', 'Personas', 'User Journey Mapping', 'Wireframing',
                'A/B Testing', 'Analytics', 'UX Writing', 'Accessibility'
            ],
            'Design Systems' => [
                'Design Systems', 'Component Libraries', 'Design Tokens', 'Pattern Libraries',
                'Documentation', 'Figma', 'Design Handoff', 'Design Consistency',
                'Scalable Design', 'Design Governance', 'Design Versioning'
            ],
        ];

        foreach ($courses as $course) {
            $subcategoryName = $course->subcategory->name ?? null;
            $skills = $skillMappings[$subcategoryName] ?? [];

            // Generate 3-5 relevant skills for each course
            $selectedSkills = collect($skills)
                ->shuffle()
                ->take(rand(3, 5))
                ->map(function ($skillName) {
                    return Skill::firstOrCreate(
                        ['name' => $skillName],
                        ['description' => "Proficiency in {$skillName} for modern development practices."]
                    );
                });

            // Attach skills to the course
            $course->skills()->syncWithoutDetaching($selectedSkills->pluck('id'));
        }
    }
}
