<?php

namespace App\Traits;

trait ApiResponseTrait
{
    public function successResponse($message = 'done successfully',  $flag = 'returnd data', $data, $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            $flag => $data,
          
        ],$code);
    }


    public function failedResponse($message = 'something went wrong', $code = 400)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $code);
    }
}
