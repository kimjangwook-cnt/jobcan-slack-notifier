<?php

namespace App\Services;

use App\Models\JobCanRequest;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class JobCanService
{
    const COMPLETED_REQUEST = 1;

    public static function trigger($type, $options = [])
    {
        if ($type == self::COMPLETED_REQUEST) {
            $period = $options['period'] ?? 60 * 24 * 7;
            $jobCanList = self::getCompletedRequest(null, $period);
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

    public static function getCompletedRequest($path = null, $period = 30)
    {
        $completedAfter = now()->subMinutes($period)->format('Y/m/d H:i:s');
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

    public static function getForms($path = null)
    {
        $path = $path ?? 'v1/forms';

        $client = self::getClient();
        $response = $client->request('GET', $path);

        $responseBody = json_decode($response->getBody(), true);
        $nextPath = $responseBody['next'] ?? false;
        $results = $responseBody['results'] ?? [];

        if ($nextPath) {
            $nextResults = self::getForms($nextPath);
            $results = array_merge($results, $nextResults);
        }

        return $results;
    }
}
