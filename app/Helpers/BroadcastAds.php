<?php

namespace App\Helpers;

use App\Models\deviceKey;
use App\Models\User;
use App\Models\Notification;
use Craftsys\Msg91\Client;
use Auth;
class BroadcastAds
{

    public static function getUserDetails($userIds)
    {
        $deviceKeys = deviceKey::whereIn('userId', $userIds)->pluck('userdeviceKey');
        
        return $deviceKeys->toArray();
    }
    public static function notificationAdsOrBroadcast(array $deviceTokens, $message, $title)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';

        $serverKey = env('FCM_KEY');

        $data = [
            "registration_ids" => $deviceTokens, 
            "notification" => [
                "title" => $title,
                "body" => $message,
            ]
        ];

        $encodedData = json_encode($data);

        $headers = [
            'Authorization:key=' . $serverKey,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);
        $result = curl_exec($ch);

        if ($result === FALSE) {
            die('Curl failed: ' . curl_error($ch));
        }

        curl_close($ch);

        if ($result === false) {
            $result_noti = 0;
        } else {
            $result_noti = 1;
        }

      

        return $result_noti;

        
    }


    public static function sendWhatsAppMessage($to, $message)
    {
        $accountSid = '';
        $authToken = '';
        $PhoneNumber = '';

        $client = new Client($accountSid, $authToken);

        $to = "whatsapp:" . $to;

        try {
            $message = $client->messages->create(
                $to,
                [
                    'from' => "whatsapp:" . $PhoneNumber,
                    'body' => $message,
                ]
            );

            return $message->sid;
        } catch (\Exception $e) {
            return false;
        }
    }


}
