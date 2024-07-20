<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use App\Jobs\SendSmsJob;
use App\Jobs\SendMailJob;
use App\Models\CampaignSetting;
use Illuminate\Console\Command;
use App\Helpers\EstimationReach;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TriggerBroadcast extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trigger:broadcast';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sending Scheduled Broadcast For Citizens and Leaders';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $currentTime =  Carbon::now()->format('H:i').':00';
        $broadcast  =   DB::table('broadcasts')
                        ->leftJoin('campaign_settings', 'campaign_settings.broadcastId', 'broadcasts.id')
                        ->where('campaign_settings.startDate', date('Y-m-d'))
                        ->where('campaign_settings.startTime', $currentTime)
                        ->where('broadcasts.status', 'Pending')
                        ->pluck('broadcasts.id')
                        ->toArray();
        $estimatedReach = new EstimationReach();
        foreach($broadcast as $id) {
            $estimatedReach->triggerBroadCastMessage($id);
        }
   }
}
