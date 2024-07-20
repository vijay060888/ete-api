<?php
namespace App\Helpers;
use App\Models\deviceKey;
use App\Models\EmailTemplate;
use App\Models\Notification;
use App\Models\PartyLogin;
use App\Models\Role;
use App\Models\SMSTemplate;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\FactCheck;
use Auth;
use Http;
use Mail;
use App\Jobs\SendSmsJob;

class Action {
    public static function getTemplate($template){
        $result=[];
        $template = strtolower($template);
        switch ($template) {
            case "registration successfull":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="registration_successfull";
                }
                break; 
            case "password update":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="password_update";
                }
                break;     
            case "report post":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="report_post";
                }
                break; 
            case "role upgrade":
                    $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                    if(count($data)>0){
                        $result['type']="template";
                        $result['data']=$data;
                    }else{
                        $result['type']="file";
                        $result['fileName']="role_upgrade";
                    }
                    break;  
            case "party request":
                        $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                        if(count($data)>0){
                            $result['type']="template";
                            $result['data']=$data;
                        }else{
                            $result['type']="file";
                            $result['fileName']="party_request";
                        }
                        break;  
            case "page access":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="page_access";
                }
                break;
            case "page accept":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="page_accept";
                }
                break;  
            case "page reject":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="page_reject";
                }
                break;
            case "party accept":
                    $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                    if(count($data)>0){
                        $result['type']="template";
                        $result['data']=$data;
                    }else{
                        $result['type']="file";
                        $result['fileName']="party_accept";
                    }
                    break;
            case "page request create":
                    $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                    if(count($data)>0){
                        $result['type']="template";
                        $result['data']=$data;
                    }else{
                        $result['type']="file";
                        $result['fileName']="page_request_create";
                    }
                    break;
            case "fact check":
                    $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                    if(count($data)>0){
                        $result['type']="template";
                        $result['data']=$data;
                    }else{
                        $result['type']="file";
                        $result['fileName']="fact_check";
                    }
                    break;  
            case "party reject":
                        $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                        if(count($data)>0){
                            $result['type']="template";
                            $result['data']=$data;
                        }else{
                            $result['type']="file";
                            $result['fileName']="party_reject";
                        }
                        break;
            case "voterid uploaded":
                    $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                    if(count($data)>0){
                        $result['type']="template";
                        $result['data']=$data;
                    }else{
                        $result['type']="file";
                        $result['fileName']="party_accept";
                    }
                    break;  
        case "request correction":
                $data=EmailTemplate::where('notificationType',$template)->where('status',1)->get();
                if(count($data)>0){
                    $result['type']="template";
                    $result['data']=$data;
                }else{
                    $result['type']="file";
                    $result['fileName']="party_accept";
                }
                break;  
            default:
            echo "NA";
        }
        return $result;

    }

    public static function sendEmail($mail)
    {
        $htmlBody = view('email.template', ['mail' => $mail])->render();
        Mail::send([], [], function ($message) use ($mail, $htmlBody) {
            $message->to($mail['email']);
            $message->subject($mail['subject']);
            $message->html($htmlBody);
        });
    }
public static function getSMSTemplate($template) 
    {
        $template = strtolower($template);
        $getSMSTemplate = SMSTemplate::where('SMSType',$template)->get();
        if (!$getSMSTemplate) {
            return "NA";
        }
        return [
            'type' => "template",
            'data' => $getSMSTemplate
        ];

        // }
        // $getParams = $getSMSTempalte->params;
        // $variable = ($getParams == '{#var}'); 
        // switch ($template) {
        //     case "reset password":
        //         $data=SMSTemplate::where('SMSType',$template)->get();
        //         if(count($data)>0){
        //             $result['type']="template";
        //             $result['data']=$data;
        //         }
        //         break;  
        //     case "registration successfull":
        //         $data=SMSTemplate::where('SMSType',$template)->get();
        //         if(count($data)>0){
        //             $result['type']="template";
        //             $result['data']=$data;
        //         }
        //         break;
        //     default:
        //     echo "NA";
        // }

        // switch ($template) {
        //     case "reset password":
        //     case "registration successfull":
        //     case "upgrade approval notification":
        //     case "join request notification":
        //     case "join page interest acknowledgment":
        //     case "join page request notification":
        //     case "citizen request for upgrade to party approved":
        //     case "party purchases notifications subscription expired":
        //     case "party notifications subscription promotion":
        //     case "leader purchases notifications subscription expired":
        //     case "leader purchases notifications subscription":
        //     case "leader notifications subscription promotion":
        //     case "party purchases ads subscription completed":
        //     case "party purchases ads subscription":
        //     case "party ads subscription promotion":
        //     case "leader purchases ads subscription completed":
        //     case "leader purchases ads subscription":
        //     case "leader ads subscription promotion":
        //     case "party subscription validity renewal":
        //     case "leader subscription validity expired":
        //     case "leader subscription validity renewal":
        //     case "leader purchases subscription":
        //     case "party requesting leader to join party is rejected2":
        //     case "party requesting leader to join party is rejected1":
        //     case "party requesting leader to join party is rejected3":
        //     case "party requesting leader to join party is approved2":
        //     case "party requesting leader to join party is approved1":
        //     case "party requesting leader to join party2":
        //     case "party requesting leader to join party1":
        //     case "leader request for joining a party is rejected2":
        //     case "leader request for joining a party is rejected1":
        //     case "community joining approval":
        //     case "upgrade request approval and guidelines":
        //     case "subscription upgrade request acknowledgment":
        //     case "upgrade approval and community guidelines":
        //     case "upgrade confirmation acknowledgment":
        //     case "upgrade approval notification1":
        //     case "phone number verification":
        //     case "role upgraded":
        //         $data = SMSTemplate::where('SMSType', $template)->get();
        //         if ($data->isNotEmpty()) {
        //             $result['type'] = "template";
        //             $result['data'] = $data;
        //         }
        //         break;
        //     default:
        //         echo "NA";
        // }
        
        // return $result;

    }

    public static function sendSMS($message, $phoneNumber)
    {
        $template = $message['template'];
        $tempid = $message['tempid'];
        $entityid = $message['entityid'];
        $source = $message['source'];
        $username = env('SMSUSERNAME', 'di80-yugasys');
        $password = env('SMSPASSWORD', 'nxtsms23');
        try {
            SendSmsJob::dispatch($username, $password, $phoneNumber, $source, $template, $entityid, $tempid)
                ->onQueue('sms');
                return 1;
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => 'Failed to dispatch SMS job', 'error' => 'Server Error'], 500);
        }
    }
//  public static function sendSMS($message,$phoneNumber)
//     {
//       $template = $message['template'];
//       $tempid = $message['tempid'];
//       $entityid = $message['entityid'];
//       $source =  $message['source'];
//       $username = env('SMSUSERNAME', 'di80-yugasys');
//       $password = env('SMSPASSWORD', 'nxtsms23');

//       $response = Http::get('http://alerts.digimiles.in/sendsms/bulksms', [
//         'username' => $username,
//         'password' =>  $password,
//         'type' => 0,
//         'dlr' => 1,
//         'destination' => $phoneNumber,
//         'source' =>   $source,
//         'message' =>  $template,
//         'entityid' => $entityid,
//         'tempid' => $tempid,
//     ]);
//     return   $response;
//  }

    public static function getNotification($userType, $notificationType)
    {
        $jsonFilePath = public_path('notification.json');
    
        $jsonContents = file_get_contents($jsonFilePath);
    
        $notifications = json_decode($jsonContents, true);
    
        if (isset($notifications[$userType]) && isset($notifications[$userType][$notificationType])) {
            $notificationValue = $notifications[$userType][$notificationType];
            return $notificationValue;
        } else {
            return 'Notification not found for the specified user type and notification type.';
        }
    }
    public static function createNotification($notificationFromId,$userType,$userId,$message,$notificationtype='',$notificationtypeid='',$notificationcategory='')
    { 
         $createNotificationData = [
            'type' => $userType,
            'typeId' => $userId,
            'isRead' =>0,
            'userId' => $notificationFromId,
            'notificationMessage' => $message,
            'createdBy' => Auth::user()->id,
            'updatedBy' => Auth::user()->id,
            'notificationtype' => $notificationtype,
            'notificationtypeid' => $notificationtypeid,
            'notificationcategory' => $notificationcategory,
         ];
         Notification::create($createNotificationData);
         $user = deviceKey::where('userId',$userId )->first();
         if ($user && ($userType === 'Leader' || $userType === 'Citizen' ||$userType === 'Admin')) {
            self::sendPushNotificationToUser($user->userdeviceKey, $message);
        }
        if ($user && $userType === 'Party') {
            $userIds = PartyLogin::where('partyId', $userId)->pluck('userId')->toArray();
        
            if (!empty($userIds)) {
                $deviceKeys = PartyLogin::whereIn('userId', $userIds)->pluck('deviceKey')->toArray();
                if (!empty($deviceKeys)) {
                    self::sendPushNotificationToPartyUser($deviceKeys, $message);
                }
            }
        }
        
    }
    

    public static function sendPushNotificationToPartyUser(array $deviceKeys, $message)
{
    $url = 'https://fcm.googleapis.com/fcm/send';

    $serverKey = env('FCM_KEY_PARTY');

    $data = [
        "registration_ids" => $deviceKeys,
        "notification" => [
            "title" => env('APP_NAME'),
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

    public static function sendPushNotificationToAll($message)
{
        $url = 'https://fcm.googleapis.com/fcm/send';
            
        $serverKey =  env('FCM_KEY');
        
       $firebaseToken = UserActivity::whereNotNull('deviceId')->pluck('deviceId')->all();
    
        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => env('APP_NAME'),
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
     
      
    return 1;
    }
    public static function sendPushNotificationToUser($deviceKey,$message)
    {
            $url = 'https://fcm.googleapis.com/fcm/send';
            
            $serverKey =  env('FCM_KEY');
      
            $data = [
                "to" => $deviceKey,
                "notification" => [
                    "title" => env('APP_NAME'),
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
        return 1;
    }


    public static function sendPushNotificationToPartyApp($message)
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
            
        $serverKey =  env('FCM_KEY_PARTY');
        
       $firebaseToken = PartyLogin::whereNotNull('deviceKey')->pluck('deviceKey')->all();
    
        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => env('APP_NAME'),
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
     
      
    return 1;
    }
}