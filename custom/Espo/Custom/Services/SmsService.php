<?php
namespace Espo\Custom\Services;

use Espo\Core\Exceptions\BadRequest;
use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Log;
use Exception;
use Espo\Core\InjectableFactory;

class SmsService    // servico a funcionar
{
    protected $log;
    protected $injectable;
    protected $entityManager;


    protected $host;
    protected $endpoint;
    protected $licensekey;
    protected $password;
    protected $startDate;
    protected $alfaSender;
    protected $envio24;
    protected $ttl;
    protected $cc;  // centro custos


    protected $phone;
    protected $sms;


    public function __construct(
        Log $log,
        InjectableFactory $injectableFactory,
        EntityManager $entityManager,

    ) {
        $this->log = $log;
        $this->injectable = $injectableFactory;
        $this->entityManager = $entityManager;
    }



    public function sendSms($data): array
    {



        //$smsSettings = $this->entityManager->getEntity('CSmsSettings', 1);   or we can use the option ↓

        $smsSettings = $this->entityManager->getRDBRepository('CSmsSettings')
            //->where(['isActive' => true])
            ->findOne();


        if (empty($smsSettings)) {
            throw new BadRequest('SMS env. settings not loaded ! SMS Service has failed');
            $this->log->error('Settings not loaded , sms service failed');
        }


        $this->host =  $smsSettings->get('host');
        $this->endpoint = $smsSettings->get('endpoint');
        $this->licensekey = $smsSettings->get('licensekey');
        $this->password = $smsSettings->get('password');

        date_default_timezone_set('Europe/Lisbon');
        $this->startDate =  date('Y-m-d H:i:s');

        $this->phone = $data['phone'];
        $this->sms = $data['sms'];


        $send = $this->cUrl();


        $response = json_decode($send, true);
  
        return [
            'data' => $response,
            'debug' =>  [
                'host' => $this->host,
                'endpoint' => $this->endpoint,
                'licensekey' => $this->licensekey,
                'password' => $this->password,
                'startDate' => $this->startDate,
                'phone' => $this->phone,
                'sms' => $this->sms

            ]
        ];
    }



    private function cUrl(): string| bool
    {

        $curl =  curl_init();
        curl_setopt_array(
            $curl,
            [
                CURLOPT_URL => $this->endpoint,
                CURLOPT_RETURNTRANSFER =>  true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST  => 'POST',
                CURLOPT_POSTFIELDS  =>
                'account=' . $this->host . '&' .
                    'licensekey=' .  $this->licensekey . '&' .
                    'phoneNumber=' . $this->phone . '&' .
                    'messageText=' . $this->sms,
                'alfaSender=' . $this->alfaSender . '&' .
                    'envio24h=' . $this->envio24 . '&' .
                    'TTL=' . $this->ttl . '&' .
                    'startDate=' . $this->startDate,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                )

            ]
        );

        $response = curl_exec($curl);
        curl_close($curl);


        return $response;
    }
}
