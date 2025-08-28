<?php

namespace Espo\Custom\Controllers;
//funciona
//testar output https://crm.autoreno.pt/api/v1/CMVehicleToSale/action/getVehicles
class CMVehicleToSale extends \Espo\Core\Templates\Controllers\Base
{
	public function actionGetVehicles($params, $data, $request)
	{
		$entityManager = $this->getEntityManager();

		$selectParamsVehicle = [
			'select' => [
				'id',
				'name',
				'goToAutoReno',
				'cMTypesName',
				'titleWebsite',
				'price',
				'mileage',
				'month',
				'year',
                'audioMultimedia',
				'cMMakesName',
				'cMModelsName',
				'cMFuelsName',
				'enginePower',
				'engineCapacity',
				'cMDoorsName',
				'cMTransmissionsName',
				'cMTypesName',
				'cMColorsName',
				'images',
				'goToLanding',
				'pvp',
				'featured',
				'campaignLabel',
				'videoUrl',
				'description',
				'traction',
				'co2Emissions',
				'cMSeatsName',
				'carIucValue',
				'wltpAutonomy',
				'tollClass',
				'audioMultimedia',
				'drivingAssistance',
				'security',
				'confort',
				'cMStandsId'
			],
			'whereClause' => [
				'active' => true,
				'deleted' => false
			],
			'orderBy' => [
				['name', 'ASC']
			],
			'leftJoins' => ['cMStands']
		];


		$eVehicleList = $entityManager->getRepository('CCMVehicles')->find($selectParamsVehicle);


           // Collect all stand IDs 
        $standIds = [];
        foreach ($eVehicleList as $vehicle) {
            if ($vehicle->get('cMStandsId')) {
                $standIds[] = $vehicle->get('cMStandsId');
            }
        }



        // Fetch all stands at once (eficiencia)
        $stands = [];
        if (!empty($standIds)) {
            $standsResult = $entityManager->getRDBRepository('CCMStands')
                ->where(['id' => array_unique($standIds)])
                ->find();

            // Convert to a map for easy access
            foreach ($standsResult as $stand) {
                $stands[$stand->get('id')] = $stand;
            }
        }

		$list = [];
		foreach ($eVehicleList as $vehicle) {

			$stand = $vehicle->get('cMStands');

			$vehicleData = [
				'general' => [
					'id' => $vehicle->get('id'),
					'active' => $vehicle->get('goToAutoReno'),
					'type' => $vehicle->get('cMTypesName'),
					'title' => $vehicle->get('titleWebsite'),
					'price' => $vehicle->get('price'),
					'name' => $vehicle->get('name'),
					'km' =>  $vehicle->get('mileage'),
					'month' => $vehicle->get('month'),
					'year' => $vehicle->get('year'),
					'brand' => $vehicle->get('cMMakesName'),
					'model' => $vehicle->get('cMModelsName'),
					'fuel' => $vehicle->get('cMFuelsName'),
					'cv' => $vehicle->get('enginePower'),
					'engine' => $vehicle->get('engineCapacity'),
					'doorNo' => $vehicle->get('cMDoorsName'),
					'gearbox' => $vehicle->get('cMTransmissionsName'),
					'segment' => $vehicle->get('cMTypesName'),
					'color' => $vehicle->get('cMColorsName'),
					'photos' => $vehicle->get('images'),
					'goToLanding' => $vehicle->get('goToLanding'),
					'pvp' => $vehicle->get('pvp'),
					'featured' => $vehicle->get('featured'),
					'campaignLabel' => $vehicle->get('campaignLabel'),
					'video' => $vehicle->get('videoUrl'),
					'description' => $vehicle->get('description')
				],
				'technicalDetail' => [
					'traction' => $vehicle->get('traction'),
					'co2' => $vehicle->get('co2Emissions'),
					'seats' => $vehicle->get('cMSeatsName'),
					'iuc' => $vehicle->get('carIucValue'),
					'autonomy' => $vehicle->get('wltpAutonomy'),
					'vehicleClass' => $vehicle->get('tollClass')
				],
				'equipment' => [
					'audio' => $vehicle->get('audioMultimedia'),
					'driving' => $vehicle->get('drivingAssistance'),
					'security' => $vehicle->get('security'),
					'confort' => $vehicle->get('confort')
				],



			];


              $standId = $vehicle->get('cMStandsId');


            if ($standId && isset($stands[$standId])) {
                $stand = $stands[$standId];
                $vehicleData['stand'] = [
                    'name' => $stand->get('name'),
                    'address' => $stand->get('address'),
                    'local' => $stand->get('local'),
                    'city' => $stand->get('city'),
                    'zip' => $stand->get('zip'),
                    'phone' => $stand->get('phone')
                ];
            }

			$list[$vehicle->get('name')] = $vehicleData;
		}

		return json_encode($list);
	}
}