<?php

namespace App\Adapter\Writer;

use Illuminate\Support\Arr;
use App\Domain\Writer\SurveyCampaignWriter;
use App\Domain\Object\SurveyCampaign\SurveyCampaignForImport;
use App\Infrastructure\Dao\BumonGroupDao;
use App\Infrastructure\Dao\BumonDao;
use App\Infrastructure\Dao\BushoDao;
use App\Infrastructure\Dao\SurveyAnswerDao;
use App\Infrastructure\Dao\UserDao;

class SurveyCampaignWriterImpl implements SurveyCampaignWriter
{
    /**
     * @param SurveyCampaignForImport $campaign
     * @param int $surveyCampaignId
     */
    public function import(SurveyCampaignForImport $campaign, int $surveyCampaignId)
    {

        \DB::beginTransaction();

        foreach ($campaign->answers as $answer) {

            $userId = UserDao::where('employeeCode', $answer->employeeCode)->value('id');
            if (is_null($userId)) { continue; }

            $update = [
                'uid' => $answer->uid,
                'at' => date('Y-m-d', $answer->time),
                'quadrant' => $answer->quadrant,
            ];

            $answerId = SurveyAnswerDao::where('surveyCampaignId', $surveyCampaignId)->where('userId', $userId)->value('id');

            if (!is_null($answerId)) {
                SurveyAnswerDao::where('id', $answerId)->update($update);
            } else {
                SurveyAnswerDao::create(array_merge($update, [
                    'surveyCampaignId' => $surveyCampaignId,
                    'userId' => $userId,
                ]));
            }
        }

        \DB::commit();
    }

}
