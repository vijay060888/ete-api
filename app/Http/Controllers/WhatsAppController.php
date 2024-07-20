<?php

namespace App\Http\Controllers;

use App\traits\WhatsAppTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    use WhatsAppTrait;

    /**
     * @OA\Post(
     *     path="/whatsapp/send-template-request",
     *     summary="Send WhatsApp Template Request",
     *     tags={"WhatsApp"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Send template request",
     *         @OA\JsonContent(
     *             required={"templateName", "headerNeeded", "headerFormat", "footerNeeded", "buttonsNeeded", "bodyContent", "buttonType"},
     *             @OA\Property(property="templateName", type="string", example="communityjoinrequests4"),
     *             @OA\Property(property="headerNeeded", type="boolean", example=true),
     *             @OA\Property(property="headerFormat", type="string", example="TEXT"),
     *             @OA\Property(property="headerText", type="string", example="Community Joining Approval 4"),
     *             @OA\Property(property="footerNeeded", type="boolean", example=false),
     *             @OA\Property(property="footerText", type="string", example=null),
     *             @OA\Property(property="buttonsNeeded", type="boolean", example=true),
     *             @OA\Property(property="bodyContent", type="string", example="Dear {{1}}, Delighted to inform you of {{2}}'s approved joining request. Engage with our community by sharing insights, asking questions, or participating in conversations3."),
     *             @OA\Property(property="buttonType", type="string", example="URL"),
     *             @OA\Property(property="buttonText", type="string", example="Visit Us"),
     *             @OA\Property(property="websiteUrl", type="string", example="http://alturl.com/y5k6x")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="data", type="object", example={"some": "data"}),
     *             @OA\Property(property="message", type="string", example="Request Sent Successfully, Awaiting Verification.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Unauthorized")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Error / Data not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Data not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal Server Error",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="error", type="string", example="Internal Server Error")
     *         )
     *     ),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function sendTemplateRequest(Request $request) {
        $validatedData = $request->validate([
            'templateName' => 'required|string',
            'headerNeeded' => 'required|boolean',
            'headerFormat' => 'required|string',
            'headerText' => $request->input('headerNeeded') ? 'required_if:headerNeeded,true|string' : 'nullable|string',
            'footerNeeded' => 'required|boolean',
            'footerText' => $request->input('footerNeeded') ? 'required_if:footerNeeded,true|string' : 'nullable|string',
            'buttonsNeeded' => 'required|boolean',
            'bodyContent' => 'required|string',
            'buttonType' => 'required|string',
            'buttonText' => $request->input('buttonsNeeded') ? 'required_if:buttonsNeeded,true|string' : 'nullable|string',
            'websiteUrl' => $request->input('buttonsNeeded') ? 'required_if:buttonsNeeded,true|url' : 'nullable|url',
        ]);

        try {
            $integratedNumber = '918970066999';
            $tempalteData = [
                'integratedNumber' => $integratedNumber,
                'templateName' => $validatedData['templateName'],
                'category' => 'MARKETING', 
                'headerNeeded' => $validatedData['headerNeeded'],
                'footerNeeded' => $validatedData['footerNeeded'],
                'buttonsNeeded' => $validatedData['buttonsNeeded'],
                'headerFormat' => $validatedData['headerFormat'],
                'headerText' => $validatedData['headerText'] ?? null,
                'footerText' => $validatedData['footerText'] ?? null,
                'bodyContent' => $validatedData['bodyContent'],
                'buttonType' => $validatedData['buttonType'],
                'buttonText' => $validatedData['buttonText'] ?? null,
                'websiteUrl' => $validatedData['websiteUrl'] ?? null,
            ];

            $sendTemplate =


            $result =  $this->sendWhatstappRequestTemplate($tempalteData);
                        if ($result['status'] != 'success') {
                return response()->json(['status'=> false, 'error'=> $result['data']], 500);   
            }
            return response()->json(['status'=> true, 'data'=> $result['data'],'message'=> 'Request Sent Successfully, Awaiting Verification.'], 200);
        } catch (\Exception $e) {
            return response()->json(['status'=> false, 'error'=> $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Get(
     *     path="/api/whatsapp/get-templates",
     *     summary="Get approved templates",
     *     tags={"WhatsApp"},
     *     @OA\Parameter(
     *         name="template_status",
     *         in="query",
     *         description="Status of the templates",
     *         required=true,
     *         @OA\Schema(
     *             type="string",
     *             enum={"approved", "pending"}
     *         )
     *     ),
     *     @OA\Property(property="isforSelectBox", type="boolean", example=false),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(
     *                     property="id",
     *                     type="integer"
     *                 ),
     *                 @OA\Property(
     *                     property="name",
     *                     type="string"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid status supplied"
     *     ),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function getTemplates() {
        try {
            $integratedNumber = '918970066999';
            $filterData = [
                'template_name' => request()->template_name ?? null,
                'template_status' => request()->template_status ?? 'approved'
            ];
            $isforSelectBox = request()->isforSelectBox ?? true;
            $result = $this->getTemplateData($integratedNumber, $filterData);
            if ($result['status'] != 'success') {
                return response()->json(['status'=> false, 'error'=> $result['data']], 500);   
            }
            if($isforSelectBox) {
                $dataList = array_map(function($item) {
                    return $item['name'];
                }, $result['data']);
            } else {
                $dataList = $result['data'];
            }
            return response()->json(['status'=> true, 'data'=> $dataList, 'message'=> 'Templates Listed Successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['status'=> false, 'error'=> $e->getMessage()], 500);
        }
    }


    /**
     * @OA\Post(
     *     path="/api/whatsapp/send-template-request",
     *     summary="Send WhatsApp template request",
     *     tags={"WhatsApp"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="JSON containing template information",
     *         @OA\JsonContent(
     *             required={"templateName", "headerNeeded", "headerFormat", "headerText", "footerNeeded", "footerText", "buttonsNeeded", "bodyContent", "buttonType", "buttonText", "websiteUrl"},
     *             @OA\Property(property="templateName", type="string", example="communityjoinrequests4", description="Name of the template"),
     *             @OA\Property(property="headerNeeded", type="boolean", example=true, description="Flag indicating if header is needed"),
     *             @OA\Property(property="headerFormat", type="string", example="TEXT", description="Format of the header"),
     *             @OA\Property(property="headerText", type="string", example="Community Joining Approval 4", description="Text content of the header"),
     *             @OA\Property(property="footerNeeded", type="boolean", example=false, description="Flag indicating if footer is needed"),
     *             @OA\Property(property="footerText", type="string", example=null, description="Text content of the footer"),
     *             @OA\Property(property="buttonsNeeded", type="boolean", example=true, description="Flag indicating if buttons are needed"),
     *             @OA\Property(property="bodyContent", type="string", example="Dear {{1}}, Delighted to inform you of {{2}}'s approved joining request. Engage with our community by sharing insights, asking questions, or participating in conversations3.", description="Body content of the template"),
     *             @OA\Property(property="buttonType", type="string", example="URL", description="Type of the button"),
     *             @OA\Property(property="buttonText", type="string", example="Visit Us", description="Text content of the button"),
     *             @OA\Property(property="websiteUrl", type="string", example="http://alturl.com/y5k6x", description="URL linked to the button")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Template request sent successfully"
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request, missing or invalid parameters"
     *     ),
     *   security={{ "apiAuth": {} }}
     * )
     */
    public function sendWhatsappMessages() {
        try {            
            $integratedNumber = '918970066999';
            $templateName = request()->templateName;
            $senders = request()->senders;

            $toAndComponents = [];
            foreach($senders as $user) {
                $toAndComponents[] = [
                    "to" => ['91'.$user['number']],
                    "components" => [
                        "body_1" => [
                            "type" => "text",
                            "value" => $user['name']
                        ]
                    ]
                ];
            }
            $templateResut = $this->sendWhatsappMsg($integratedNumber, $templateName, $toAndComponents);
            if ($templateResut['status'] != 'success') {
                return response()->json(['status'=> false, 'error'=> $templateResut['data']], 500);   
            }
            return response()->json(['status'=> true, 'data'=> $templateResut['data'], 'message'=> $templateResut['data']], 200);
        } catch (\Exception $e) {
            return response()->json(['status'=> false, 'error'=> $e->getMessage()], 500);
        }
    }
}
