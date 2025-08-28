<?php
namespace Espo\Custom\Controllers;

use Espo\Core\Exceptions\Error;
use Espo\Core\Utils\Log;
use Espo\Custom\Services\ImportGeneric;
use Exception;

class CCMStands extends \Espo\Core\Templates\Controllers\Base
{
    protected Log $log;

    public function actionImportStands()
    {
        try {
            $importService = $this->injectableFactory->create(ImportGeneric::class);

            $results = $importService->save([
                'endpoint' => '/company/getstands',
                'entityType' => 'CCMStands',
                'fieldsMapping' => [
                    'externalId' => 'id',  // Maps to extenalId field (note the typo in field name)
                    'name' => 'name',
                    'description' => 'description',
                    'status' => 'status',
                    'main' => 'main',
                    'email' => 'email',
                    'addressStreet' => 'address_street',
                    'addressZipCode' => 'address_zip_code',
                    'addressLocal' => 'address_local',
                    'districtId' => 'district_id',
                    'districtName' => 'district_name',
                    'cityId' => 'city_id',
                    'cityName' => 'city_name',
                    'coordinates' => 'coordinates',
                    'phonenumber' => 'phonenumber',
                    'mobilenumber' => 'mobilenumber',
                    'mobilenumber2' => 'mobilenumber2',
                    'mobilenumber3' => 'mobilenumber3',
                    'fax' => 'fax',
                    'facebook' => 'facebook',
                    'instagram' => 'instagram',
                    'youtube' => 'youtube',
                    'messenger' => 'messenger',
                    'whatsapp' => 'whatsapp',
                    'embedgoogle' => 'embedgoogle',
                    'linkgoogle' => 'linkgoogle',
                    'numbervehicles' => 'numbervehicles'
                ],
                'rebuild' => false
            ]);

            return [
                'status' => 'success',
                'data' => $results
            ];
        } catch (Exception $e) {
            throw new Error("Stands import failed: " . $e->getMessage());
        }
    }
}