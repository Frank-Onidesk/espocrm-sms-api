<?php

namespace Espo\Custom\Controllers;

use Espo\Custom\Services\SmsService;
use Espo\Core\Api\Request;
use Espo\Core\Exceptions\BadRequest;

class CSMS  extends \Espo\Core\Templates\Controllers\Base
{


    public function actionSendSms(Request $request)
    {

        $data = $request->getParsedBody();

       
      
        
        if (empty($data->phone) || empty($data->sms)) {
            throw new BadRequest("Missing phone number or SMS text");
        }

        $smsService = $this->injectableFactory->create(SmsService::class);


        return $smsService->sendSms([
            'phone' => '', // $data->phone,
            'sms' => $data->sms,
            'id' => $data->id
        ]);
    }
}
