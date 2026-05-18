<?php

namespace App\Http\Controllers;

use App\Models\PromotionalTool;
use Illuminate\Http\Request;

class PromotionalToolController extends Controller
{
    public function index()
    {
        try{
            $tools = PromotionalTool::all();
            return response()->json($tools);
        }
        catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
