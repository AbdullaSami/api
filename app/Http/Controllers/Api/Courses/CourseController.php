<?php

namespace App\Http\Controllers\Api\Courses;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CourseAccessService;
use App\Models\Course;
class CourseController extends Controller
{
    protected $courseAccessService;

    public function __construct(CourseAccessService $courseAccessService)
    {
        $this->courseAccessService = $courseAccessService;
    }

    public function index()
    {
        try{
            $courses = Course::all();
            return response()->json([
                'success' => true,
                'data' => $courses
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve courses: ' . $e->getMessage()
            ], 500);
            }
    }

    public function show(Course $course)
    {
        try{
            return response()->json([
                'success' => true,
                'data' => $course->load('lessons', 'skills') // Eager load lessons and skills with the course
            ]);
        }catch(\Exception $e){
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course: ' . $e->getMessage()
            ], 500);
        }
    }
}
