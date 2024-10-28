<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;

class JobCanRequest extends BaseModel
{
    protected $table = 'job_can_requests';

    protected $fillable = [
        'id',
        'title',
        'form_id',
        'form_name',
        'form_type',
        'settlement_type',
        'status',
        'applied_date',
        'applicant_code',
        'applicant_last_name',
        'applicant_first_name',
        'applicant_group_name',
        'applicant_position_name',
        'proxy_applicant_last_name',
        'proxy_applicant_first_name',
        'group_name',
        'group_code',
        'project_name',
        'project_code',
        'flow_step_name',
        'is_content_changed',
        'total_amount',
        'pay_at',
        'final_approval_period',
        'final_approved_date',
        'applicant_group_code',
    ];

    protected $casts = [
        'applied_date' => 'datetime:Y-m-d H:i:s',
        'final_approved_date' => 'datetime:Y-m-d H:i:s',
    ];

    public static function getFillableInfo($info)
    {
        return [
            'id' => $info['id'],
            'title' => $info['title'],
            'form_id' => $info['form_id'],
            'form_name' => $info['form_name'],
            'form_type' => $info['form_type'],
            'settlement_type' => $info['settlement_type'],
            'status' => $info['status'],
            'applied_date' => $info['applied_date'],
            'applicant_code' => $info['applicant_code'],
            'applicant_last_name' => $info['applicant_last_name'],
            'applicant_first_name' => $info['applicant_first_name'],
            'applicant_group_name' => $info['applicant_group_name'],
            'applicant_position_name' => $info['applicant_position_name'],
            'proxy_applicant_last_name' => $info['proxy_applicant_last_name'],
            'proxy_applicant_first_name' => $info['proxy_applicant_first_name'],
            'group_name' => $info['group_name'],
            'group_code' => $info['group_code'],
            'project_name' => $info['project_name'],
            'project_code' => $info['project_code'],
            'flow_step_name' => $info['flow_step_name'],
            'is_content_changed' => $info['is_content_changed'],
            'total_amount' => $info['total_amount'],
            'pay_at' => $info['pay_at'],
            'final_approval_period' => $info['final_approval_period'],
            'final_approved_date' => $info['final_approved_date'],
            'applicant_group_code' => $info['applicant_group_code'],
        ];
    }

    public static function upsertAll($listOfJobCanInfo)
    {
        $insertList = [];


        $idList = collect($listOfJobCanInfo)->pluck('id')->values()->toArray();

        $inDb = JobCanRequest::whereIn('id', $idList)->get();

        foreach ($listOfJobCanInfo as $info) {
            $parsedInfo = self::getFillableInfo($info);

            if ($inDb->contains('id', $parsedInfo['id'])) {
                $inDb->find($parsedInfo['id'])->update($parsedInfo);
            } else {
                $targetForm = config('env.jobcan_target_form');
                if (in_array($parsedInfo['form_id'], $targetForm)) {
                    $insertList[] = $parsedInfo;
                    JobCanRequest::create($parsedInfo);
                    Log::info("完了した依頼: 【" . $parsedInfo['form_id'] . "】 " . $parsedInfo['title']);
                } else {
                    Log::info("完了したが対象外の依頼: 【" . $parsedInfo['form_id'] . "】 " . $parsedInfo['title']);
                }
            }
        }

        return $insertList;
    }
}
