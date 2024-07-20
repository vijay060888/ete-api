<?php

namespace App\Traits;

use Illuminate\Support\Facades\Http;

trait WhatsAppTrait
{

    function sendWhatstappRequestTemplate($data) {
        $components = [];
        if ($data['headerNeeded']) {
            $components[] = [
                "type" => "HEADER",
                "format" => $data['headerFormat'],
                "text" => $data['headerText']
            ];
        }
        $components[] = [
            "type" => "BODY",
            "text" => $data['bodyContent']
        ];

        if ($data['footerNeeded']) {
          $components[] = [
            "type" => "FOOTER",
            "text" => $data['footerText']
          ];
        }

        if ($data['buttonsNeeded']) {
            $components[] = [
                "type" => "BUTTONS",
                "buttons" => [
                    [
                        "type" => $data['buttonType'],
                        "text" => $data['buttonText'],
                        "url" => $data['websiteUrl']
                    ]
                ]
            ];
        }
        
        $template = [
                "integrated_number" => $data['integratedNumber'],
                "template_name" => $data['templateName'],
                "language" => "en",
                "category" => $data['category'],
                "button_url" => "true",
                "components" => $components
        ];
       
        $response = Http::withHeaders([
            'authkey' => env('whatsappAuthKey'),
            'Content-Type' => 'application/json',
        ])->post('https://api.msg91.com/api/v5/whatsapp/client-panel-template/', $template);
        
        return $response->json();
    }

    function getTemplateData($integratedNumber, $filterData) {
        $response = Http::withHeaders([
            'authkey' => env('whatsappAuthKey'),
            'Content-Type' => 'application/json',
        ])->get('https://api.msg91.com/api/v5/whatsapp/get-template-client/'.$integratedNumber, $filterData);
        return $response->json();
    }

    function sendWhatsappMsg($integratedNumber, $templateName, $toAndComponents) {
        
        $data = [
            "integrated_number" => $integratedNumber,
            "content_type" => "template",
            "payload" => [
                "messaging_product" => "whatsapp",
                "type" => "template",
                "template" => [
                    "name" => $templateName,
                    "language" => [
                        "code" => "en",
                        "policy" => "deterministic"
                    ],
                    "to_and_components" => $toAndComponents
                ]
            ]
        ];

        $response = Http::withHeaders([
            'authkey' => env('whatsappAuthKey'),
            'Content-Type' => 'application/json',
        ])->post('https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/', $data);
        
        return $response->json();
    }
}