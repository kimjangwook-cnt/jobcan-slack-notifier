<?php

namespace App\Services;

use App\Models\JobCanRequest;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class JobCanService
{
    const COMPLETED_REQUEST = 1;

    public static function trigger($type)
    {
        if ($type == self::COMPLETED_REQUEST) {
            $jobCanList = self::getCompletedRequest();
            $insertedList = JobCanRequest::upsertAll($jobCanList);

            if (count($insertedList) > 0) {
                SlackService::completedRequest($insertedList);
            }

            return $insertedList;
        }

        return null;
    }

    public static function getClient()
    {
        return new Client([
            'base_uri' => 'https://ssl.wf.jobcan.jp/wf_api/',
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Token ' . config('env.jobcan_api_key'),
            ],
        ]);
    }

    public static function getCompletedRequest($path = null)
    {
        $completedAfter = now()->subMinutes(30)->format('Y/m/d H:i:s');
        $path = $path ?? 'v2/requests/?status=completed&completed_after=' . $completedAfter;


        $client = self::getClient();
        $response = $client->request('GET', $path);

        $responseBody = json_decode($response->getBody(), true);
        $nextPath = $responseBody['next'] ?? false;
        $results = $responseBody['results'] ?? [];

        if ($nextPath) {
            $nextResults = self::getCompletedRequest($nextPath);
            $results = array_merge($results, $nextResults);
        }

        return $results;
    }
}
