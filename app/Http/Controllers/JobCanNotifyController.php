<?php

namespace App\Http\Controllers;

use App\Services\JobCanService;

class JobCanNotifyController extends Controller
{
    public function index()
    {
        $insertedList = JobCanService::trigger(JobCanService::COMPLETED_REQUEST);

        return response()->json($insertedList);
    }
}
