<?php
namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class HttpHelper
{
    public static function checkText($text)
    {
        // $url = "https://mlengines.engagetoelect.com/text_model";
        $url = "https://abusetext.engagetoelect.com/text_model";
        
        $ch = curl_init($url);
        
        $data = [
            'text' => $text,
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }
        
        curl_close($ch);
        
        return json_decode($response);
    }
    
    public static function checkPoliticalNonPolitical($text)
    {
        // $url = "https://mlengines.engagetoelect.com/political_nonpolitical";
        // $url = "https://pnp.engagetoelect.com/political_nonpolitical";
        $url = "https://pnp.engagetoelect.com/political_nonpolitical_new";
        
        $ch = curl_init($url);
        
        $data = [
            'text' => $text,
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }
        
        curl_close($ch);
        
        return json_decode($response);
    }
    
    public static function checkSentiment($text)
    {
        // $url = "https://sentiment.engagetoelect.com/sentiment";
        $url = "https://sentiment.engagetoelect.com/sentiment_new";
        
        $ch = curl_init($url);
        
        $data = [
            'text' => $text,
        ];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: multipart/form-data',
        ]);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);
        }
        
        curl_close($ch);
        
        return json_decode($response);
    }

    public static function checkImage($imageURL)
    {
        try {
            // $url = "https://mlengines.engagetoelect.com/abusive_image_model";
            // $url = "https://abimage.engagetoelect.com/abusive_image_model";
            $url = "https://abimage.engagetoelect.com/abusive_image_model_new";
    
            $ch = curl_init($url);
    
            $data = [
                'image_file' => $imageURL,
            ];
    
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: multipart/form-data',
            ]);
    
            $response = curl_exec($ch);
    
            if (curl_errno($ch)) {
                throw new \Exception('cURL Error: ' . curl_error($ch));
            }
    
            curl_close($ch);
    
            return $response;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function trendingPosts()
    {
        try {
            $url = "https://trendengine.engagetoelect.com/";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('cURL Error: ' . curl_error($ch));
            }
            curl_close($ch);
            $responseData = json_decode($response, true);
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response');
            }
            return $responseData;
        } catch (\Exception $e) {
            return null;
        }
    } 

    public static function similarAssembly($assemblyid)
    {
        try {
            $url = "https://sconst.engagetoelect.com/ass_names/" .$assemblyid;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('cURL Error: ' . curl_error($ch));
            }
            curl_close($ch);
            $responseData = json_decode($response, true);
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response');
            }
            return $responseData;
        } catch (\Exception $e) {
            return null;
        }
    }
    

    public static function similarLeaders($userId)
    {
        try {
            $url = "https://similarengine.engagetoelect.com/get_similar_politicians/" .$userId;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('cURL Error: ' . curl_error($ch));
            }
            curl_close($ch);
            $responseData = json_decode($response, true);
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response');
            }
            return $responseData;
        } catch (\Exception $e) {
            return null;
        }
    }
    

    public static function similarParties()
    {
        try {
            $url = "https://sconst.engagetoelect.com/get_parties";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            if (curl_errno($ch)) {
                throw new \Exception('cURL Error: ' . curl_error($ch));
            }
            curl_close($ch);
            $responseData = json_decode($response, true);
            if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Error decoding JSON response');
            }
            return $responseData;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function checkImageExtensions($storyContent)
    {
        try {
            $extensions = [
                'jpg', 'jpeg', 'png', 'gif', 'bmp', 
                'tiff', 'tif', 'webp', 'svg', 'heif', 
                'heic', 'raw', 'cr2', 'nef', 'arw', 
                'orf', 'psd'
            ];
            if (filter_var($storyContent, FILTER_VALIDATE_URL)) {
                $path = parse_url($storyContent, PHP_URL_PATH);
                $fileExtension = pathinfo($path, PATHINFO_EXTENSION);
            } else {
                $fileExtension = pathinfo($storyContent, PATHINFO_EXTENSION);
            }
            $fileExtension = strtolower($fileExtension);
            return in_array($fileExtension, $extensions) ? 1 : 0;
        } catch (\Exception $e) {
            return null;
        }
    }

}
