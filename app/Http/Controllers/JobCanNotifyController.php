<?php

namespace App\Http\Controllers;

use App\Services\JobCanService;

class JobCanNotifyController extends Controller
{
    public function index()
    {
        $insertedList = JobCanService::trigger(JobCanService::COMPLETED_REQUEST, ['period' => 60 * 8]);

        return response()->json($insertedList);
    }

    public function forms()
    {
        $forms = JobCanService::getForms();

        return response()->json($forms);
    }
}
