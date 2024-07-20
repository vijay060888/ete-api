<?php


namespace App\Helpers;
use Request;
use App\Models\LogActivity as LogActivityModel;


class LogActivity
{


    public static function addToLog($subject,$errorlog)
    {
        
    	$log = [];
    	$log['subject'] = $subject;
        $log['errorlog']=$errorlog;
    	$log['url'] = Request::fullUrl();
    	$log['method'] = Request::method();
    	$log['ip'] = Request::ip();
    	$log['agent'] = Request::header('user-agent');
    	$log['user_id'] = auth()->check() ? auth()->user()->id : 'Unauthorized';
    	LogActivityModel::create($log);
    }


    public function getIp()
    {
      return  Request::ip();
    }
    public static function logActivityLists()
    {
    	return LogActivityModel::latest()->get();
    }

    public static function logActivityClear()
    {
        return LogActivityModel::truncate();
    }


}