<?php
namespace App\Jobs;

use App\Helpers\LogActivity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $username;
    protected $password;
    protected $phoneNumber;
    protected $source;
    protected $template;
    protected $entityid;
    protected $tempid;

    public function __construct($username, $password, $phoneNumber, $source, $template, $entityid, $tempid)
    {
        $this->username = $username;
        $this->password = $password;
        $this->phoneNumber = $phoneNumber;
        $this->source = $source;
        $this->template = $template;
        $this->entityid = $entityid;
        $this->tempid = $tempid;
    }

    public function handle()
    {
        try {
            Http::get('http://alerts.digimiles.in/sendsms/bulksms', [
                'username' => $this->username,
                'password' => $this->password,
                'type' => 0,
                'dlr' => 1,
                'destination' => $this->phoneNumber,
                'source' => $this->source,
                'message' => $this->template,
                'entityid' => $this->entityid,
                'tempid' => $this->tempid,
            ]);
        } catch (\Exception $e) {
            LogActivity::addToLog($e->getMessage(), $e->getTraceAsString());
        }
    }
}


