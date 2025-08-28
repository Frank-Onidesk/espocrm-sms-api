<?php

namespace Espo\Custom\Controllers;

use Espo\Core\Exceptions\Error;
use Espo\Custom\Services\ImportCMVehicles;

class CCMVehicles extends \Espo\Core\Templates\Controllers\Base
{
    public function actionImportVehicles($params, $data, $request): array
    {
        try {
            $importService = $this->injectableFactory->create(ImportCMVehicles::class);

            $vehicleImportConfig = [
                'endpoint' => '/vehicle/getvehicle',
                'entityType' => 'CCMVehicles',
                'fieldsMapping' => [
                    'vehicleId' => 'vehicle_id',
                    'name' => 'license_plate',
                    'createDate' => 'create_date',
                    'changeDate' => 'change_date',
                    'reference' => 'reference',
                    'carReserved' => 'reserved', #
                    'carSold' => 'sold',
                    'carAvailableSoon' => 'available_soon', #
                    'soldToDistrict' => 'sold_to_district',
                    'soldToCity' => 'sold_to_city',
                    'tagImg' => 'tag_img',
                    'year' => 'year',
                    'month' => 'month',
                    'mileage' => 'mileage',
                    'price' => 'price.price',
                    #'exportPrice' => 'price.export_price',
                    #'promotion' => 'price.promotion',
                    #'negotiable' => 'price.negotiable',
                    #'commercial' => 'price.commercial',
                    #'monthlyAmount' => 'price.monthly_amount',
                    #'exportMonthlyAmount' => 'price.export_monthly_amount',
                    'priceWithoutIva' => 'price.without_iva',
                    'priceWithoutIsv' => 'price.without_isv',
                    'ivaDiscriminated' => 'price.iva_discriminated',
                    'ivaDeductible' => 'price.iva_deductible',
                    'textCampaigns' => 'price.text_campaigns',
                    'dateCampaigns' => 'price.date_campaigns',
                    'version' => 'version',
                    'versionId' => 'version_id',
                    'observations' => 'observations',
                    'observationsEnglish' => 'observations_english',
                    'traction' => 'traction',
                    'enginePower' => 'engine_power',
                    'engineCapacity' => 'engine_capacity',
                    #'hashtag' => 'hashtag',
                    'supplierType' => 'supplier_type',
                    'email' => 'email',
                    'licensePlate' => 'license_plate',
                    'licensePlateDate' => 'license_plate_date',
                    'licensePlateExport' => 'license_plate_export',
                    'active' => 'active',
                    'videoUrl' => 'video_url',
                    'videoUrl2' => 'video_url2',
                    'equipments' => 'equipments',
                    'vin' => 'vin',
                    'exportVin' => 'export_vin',
                    'carSecondKey' => 'second_key',
                    #'image360' => 'image360',
                    'registrations' => 'registrations',
                    'carNacional' => 'nacional',
                    'insertionType' => 'insertion_type',
                    'carInsertionTypeSv' => 'insertion_type_sv',
                    'dealAcceptReturns' => 'accept_returns',
                    #'byOrder' => 'by_order',
                    'carImported' => 'imported',
                    'rpm' => 'rpm',
                    'engineTorque' => 'engine_torque',
                    'performanceZeroHundred' => 'performance_zero_hundred',
                    'maximumSpeed' => 'maximum_speed',
                    'urbanConsumption' => 'urban_consumption',
                    'combinedConsumption' => 'combined_consumption',
                    'extraUrbanConsumption' => 'extra_urban_consumption',
                    'yearConstruction' => 'year_construction',
                    'carIucValue' => 'iuc.value',
                    'carIucMonth' => 'iuc.month',
                    'carInspectionExpiration' => 'inspection_expiration',
                    'newValueWithoutExtras' => 'new_value_without_extras',
                    'numberCylinders' => 'number_cylinders',
                    'tollClass' => 'toll_class',
                    'maintenanceHistory' => 'maintenance_history',
                    'carFinancingPossibility' => 'financing_possibility',
                    'stampTax' => 'stamp_tax',
                    #'bed' => 'semi_trucks.bed',
                    #'height' => 'semi_trucks.height',
                    #'length' => 'semi_trucks.length',
                    #'suspension' => 'semi_trucks.suspension',
                    #'load' => 'semi_trucks.load',
                    #'body' => 'semi_trucks.body',
                    'tanValue' => 'credit.taeg_value',
                    'creditTanValue' => 'credit.tan_value',
                    'downPayment' => 'credit.down_payment',
                    'monthPayment' => 'credit.month_payment',
                    'mtic' => 'credit.mtic',
                    'cpp' => 'credit.cpp',
                    'cac' => 'credit.cac',
                    'financial' => 'credit.financial',
                    'legalText' => 'credit.legal_text',
                    'creditDate' => 'credit.date',
                    #'isv' => 'importation.isv',
                    #'transport' => 'importation.transport',
                    #'legalization' => 'importation.legalization',
                    #'legalCosts' => 'importation.legal_costs',
                    'carManuals' => 'manuals',
                    'co2Emissions' => 'co2_emissions',
                    'bateryType' => 'batery_type',
                    'wltpAutonomy' => 'wltp_autonomy',
                    'lifeInsurance' => 'life_insurance',
                    'district' => 'district',
                    'county' => 'county',
                    'origin' => 'origin',
                    'exportEasysite' => 'exportation.export_easysite',
                    'exportPortals' => 'exportation.export_portals',
                    'exportExportCarmine' => 'exportation.export_carmine',
                    'exportExportStandvirtual' => 'exportation.export_standvirtual',
                    'activeStandvirtual' => 'exportation.active_standvirtual',
                    'renewStandvirtual' => 'exportation.renew_standvirtual',
                    'exportExportOlx' => 'exportation.export_olx',
                    'exportRenewOlx' => 'exportation.renew_olx',
                    'exportAutosapo' => 'exportation.export_autosapo',
                    'activeAutosapo' => 'exportation.active_autosapo',
                    'exportHellocar' => 'exportation.export_hellocar',
                    'activeHellocar' => 'exportation.active_hellocar',
                    'renewHellocar' => 'exportation.renew_hellocar',
                    'exportPiscapisca' => 'exportation.export_piscapisca',
                    'activePiscapisca' => 'exportation.active_piscapisca',
                    'renewPiscapisca' => 'exportation.renew_piscapisca',
                    'exportCustojusto' => 'exportation.export_custojusto',
                    'activeCustojusto' => 'exportation.active_custojusto',
                    'exportSeucarro' => 'exportation.export_seucarro',
                    'carmineId' => 'status_exportation.carmine.id',
                    'carmineStatus' => 'status_exportation.carmine.status'



                ],
                'images' => [
                    'path' => 'images', // The path in the JSON where images array is located
                    'handler' => function (array $images) {
                        // Sort images with main first, then by order
                        usort($images, function ($a, $b) {
                            return ($b['main'] ?? false) <=> ($a['main'] ?? false)
                                ?: ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
                        });

                        // Extract just the image URLs
                        return array_map(function ($image) {
                            return $image['image'] ?? null;
                        }, $images);
                    }
                ],
                'linkToEntity' => [
                    [
                        'relationName' => 'cmStand',
                        'relatedEntityType' => 'CCMStands',
                        'carmineIdField' => 'stand.id'
                    ],


                    /*[
                        'relatedEntityType' => 'CCMColors',
                        'relationName' => 'cMColors',
                        'carmineIdField' => 'color.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMDoors',
                        'relationName' => 'cMDoors',
                        'carmineIdField' => 'doors.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMFuels',
                        'relationName' => 'cMFuels',
                        'carmineIdField' => 'fuel.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMMakes',
                        'relationName' => 'cMMakes',
                        'carmineIdField' => 'make.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMModels',
                        'relationName' => 'cMModels',
                        'carmineIdField' => 'model.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMSeats',
                        'relationName' => 'cMSeats',
                        'carmineIdField' => 'seats.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMStands',
                        'relationName' => 'cMStands',
                        'carmineIdField' => 'stand.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMStates',
                        'relationName' => 'cMStates',
                        'carmineIdField' => 'state.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMTransmissions',
                        'relationName' => 'cMTransmissions',
                        'carmineIdField' => 'transmission.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMTypes',
                        'relationName' => 'cMTypes',
                        'carmineIdField' => 'type.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMWarranties',
                        'relationName' => 'cMWarranties',
                        'carmineIdField' => 'warranty.id'
                    ],
                    [
                        'relatedEntityType' => 'CCMEquipments',
                        'relationName' => 'cMEquipments',
                        'carmineIdField' => 'equipments.id'
                    ]*/

                ],
                'rebuild' => true
            ];

            $results = $importService->save($vehicleImportConfig);

            return [
                'status' => 'success',
                'data' => $results
            ];
        } catch (\Exception $e) {
            throw new Error("Vehicle import failed: " . $e->getMessage());
        }
    }
}
