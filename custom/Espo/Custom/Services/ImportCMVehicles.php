<?php

namespace Espo\Custom\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Log;
use Espo\Custom\Classes\Carmine;
use Exception;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;
use InvalidArgumentException;

class ImportCMVehicles extends ImportGeneric
{

    protected $log;
    protected $injectable;
    protected $entityManager;
    protected $dataManager;

    public function __construct(
        Log $log,
        InjectableFactory $injectableFactory,
        EntityManager $entityManager,
        DataManager $dataManager
    ) {
        $this->log = $log;
        $this->injectable = $injectableFactory;
        $this->entityManager = $entityManager;
        $this->dataManager = $dataManager;
    }

    public function save(array $config): array
    {
        $endpoint = $config['endpoint'];
        $entityType = $config['entityType'];
        $fieldMapping = $config['fieldsMapping'];
        $rebuild = $config['rebuild'] ?? true;

        $this->log->info('Starting ' . $entityType . ' import from endpoint: ' . $endpoint);

        $carmine = $this->injectable->create(Carmine::class);
        $response = $carmine->importFromCarmine($endpoint);

        $importData = json_decode($response, true);

        return $importData;
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = 'Invalid JSON data received: ' . json_last_error_msg();
            $this->log->error($errorMsg . ' Response: ' . substr($response, 0, 1000));
            throw new Exception($errorMsg);
        }

        $results = [
            'total' => count($importData),
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => []
        ];

        $batchSize = 20;
        $processed = 0;

        foreach ($importData as $item) {
            $transactionManager = $this->entityManager->getTransactionManager();
            $transactionManager->start();

            try {
                $externalId = $this->getNestedValue($item, $fieldMapping['vehicleId']);
                $nameValue = $this->getNestedValue($item, $fieldMapping['name']);

                if (empty($externalId)) {
                    $results['skipped']++;
                    $transactionManager->commit();
                    continue;
                }

                // Prepare all entity data from field mapping
                $entityData = [
                    'vehicleId' => $externalId,
                    'name' => $nameValue
                ];

                // Add all mapped fields
                foreach ($fieldMapping as $entityField => $carmineField) {
                    if (!in_array($entityField, ['vehicleId', 'name'])) {
                        $value = $this->getNestedValue($item, $carmineField);
                        if ($value !== null) {
                            $entityData[$entityField] = $value;
                        }
                    }
                }

                // Find or create entity
                $entity = $this->entityManager->getRDBRepository($entityType)
                    ->where(['vehicleId' => $externalId])
                    ->findOne();

                if ($entity) {
                    // Update existing entity
                    $entity->set($entityData);
                    $this->entityManager->saveEntity($entity);
                    $results['updated']++;
                    $this->log->debug("Updated {$entityType} {$externalId}");
                } else {
                    // Create new entity
                    $entity = $this->entityManager->createEntity($entityType, $entityData);
                    $results['created']++;
                    $this->log->debug("Created new {$entityType} {$externalId}");
                }

                if (!empty($config['images'])) {
                    $this->processImages($entity, $item, $config['images']);
                    $this->entityManager->saveEntity($entity); // Save again with images
                }


                // Process relationships
                if (!empty($config['linkToEntity']) && $entity) {
                    foreach ((array)$config['linkToEntity'] as $link) {
                        try {

                            $this->processRelationship($item, $link, $entity, $results);
                        } catch (Exception $e) {
                            $this->logRelationError($e, $link, $externalId, $results);
                        }
                    }
                }




                $transactionManager->commit();

                if (++$processed % $batchSize === 0) {
                    //counting processed records
                    $this->log->info("Processed {$processed} of {$results['total']} {$entityType} records");
                }
            } catch (Exception $e) {
                $transactionManager->rollback();
                $this->logImportError($e, $externalId ?? 'unknown', $entityType, $results);
            }
        }

        if ($rebuild) {
            $this->dataManager->rebuild();
        }

        $this->logResults($entityType, $results);
        return $results;
    }




    // extract images
    protected function processImages($vehicle, array $item, array $imagesConfig): void
    {
        //
        $images = $this->getNestedValue($item, $imagesConfig['path']);

        if (empty($images) || !is_array($images)) {
            return;
        }


        $imageUrls = $imagesConfig['handler']($images);

        $imageUrls = array_filter($imageUrls, function ($url) {
            return !empty($url) && is_string($url);
        });

        // Reset array keys if needed
        $imageUrls = array_values($imageUrls);


        $vehicle->set('images', $imageUrls);

        // first image is main
        if (!empty($imageUrls[0]) && $vehicle->has('image')) {
            $vehicle->set('image', $imageUrls[0]);
        }
    }


    /* protected function processRelationship($item, $link, $entity, array &$results)
    {
        $relatedEntityId = $this->getNestedValue($item, $link['carmineIdField']);
        if (empty($relatedEntityId)) {
            $this->log->debug("Skipping relation {$link['relationName']} - no ID found");
            return;
        }

        // Search for related entity using externalId
        $relatedEntity = $this->entityManager->getRDBRepository($link['relatedEntityType'])
            ->where(['externalId' => $relatedEntityId])
            ->findOne();

        if (!$relatedEntity) {
            $this->log->notice("Related entity {$link['relatedEntityType']} with ID {$relatedEntityId} not found");
            $results['errors'][] = [
                'externalId' => $entity->get('vehicleId'),
                'relationError' => "Related entity {$link['relatedEntityType']} with ID {$relatedEntityId} not found"
            ];
            return;
        }

        $relationName = $link['relationName'];

        try {
            // configurar a foreign key directamentedirectly (são relações do tipo  belongsTo relationships)
            $foreignKey = $relationName . 'Id';
            $entity->set($foreignKey, $relatedEntity->getId());

            // salvar a entidade com a nova relação relationship
            $this->entityManager->saveEntity($entity);

            $this->log->debug("Set relationship {$relationName} for vehicle {$entity->get('vehicleId')} to {$link['relatedEntityType']} {$relatedEntityId}");
        } catch (Exception $e) {
            throw new Exception("Failed to set relationship {$relationName}: " . $e->getMessage());
        }
    }*/

    protected function processRelationship($item, $link, $entity, array &$results)
    {
        $relatedEntityId = $this->getNestedValue($item, $link['carmineIdField']);

        // Add debug logging
        $this->log->debug("Processing relationship: " . $link['relationName']);
        $this->log->debug("Looking for related entity: " . $link['relatedEntityType'] .
            " with externalId: " . $relatedEntityId);

        if (empty($relatedEntityId)) {
            $this->log->debug("Skipping relation {$link['relationName']} - no ID found");
            return;
        }

        // Search for related entity using externalId
        $relatedEntity = $this->entityManager->getRDBRepository($link['relatedEntityType'])
            ->where(['externalId' => $relatedEntityId])
            ->findOne();

        if (!$relatedEntity) {
            $this->log->notice("Related entity {$link['relatedEntityType']} with ID {$relatedEntityId} not found");
            $results['errors'][] = [
                'externalId' => $entity->get('vehicleId'),
                'relationError' => "Related entity {$link['relatedEntityType']} with ID {$relatedEntityId} not found"
            ];
            return;
        }

        $this->log->debug("Found related entity: " . $relatedEntity->getId() .
            " for externalId: " . $relatedEntityId);

        $relationName = $link['relationName'];

        try {
            // Set the foreign key directly (for belongsTo relationships)
            $foreignKey = $relationName . 'Id';
            $this->log->debug("Setting foreign key: " . $foreignKey . " to value: " . $relatedEntity->getId());

            $entity->set($foreignKey, $relatedEntity->getId());

            // Save the entity with the new relationship
            $this->entityManager->saveEntity($entity);

            $this->log->debug("Set relationship {$relationName} for vehicle {$entity->get('vehicleId')} to {$link['relatedEntityType']} {$relatedEntityId}");
        } catch (Exception $e) {
            throw new Exception("Failed to set relationship {$relationName}: " . $e->getMessage());
        }
    }

    protected function getNestedValue(array $item, string $field)
    {
        $parts = explode('.', $field);
        $value = $item;

        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return null;
            }
            $value = $value[$part];
        }

        if (in_array($field, [
            'create_date',
            'modified_date',
            'change_date',
            'license_plate_date',
            'inspection_expiration',
            'credit_date'
        ])) { // all date type fields 
            return $this->convertDateFormat($value);
        }

        return $value;
    }

    protected function convertDateFormat(string $dateString): ?string
    {
        if (empty($dateString)) {
            return null;
        }

        // Handle DD/MM/YYYY format
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $dateString)) {
            $date = \DateTime::createFromFormat('d/m/Y H:i:s', $dateString);
            return $date ? $date->format('Y-m-d H:i:s') : null;
        }

        // Handle other formats if needed
        return $dateString;
    }

    protected function logRelationError(Exception $e, array $link, string $externalId, array &$results)
    {
        $errorMsg = "Failed to link relation {$link['relationName']}: " . $e->getMessage();
        $this->log->error($errorMsg);
        $results['errors'][] = [
            'externalId' => $externalId,
            'relationError' => $errorMsg
        ];
    }

    protected function logImportError(Exception $e, string $externalId, string $entityType, array &$results)
    {
        $errorMsg = "{$entityType} import failed for ID {$externalId}: " . $e->getMessage();
        $this->log->error($errorMsg);
        $results['errors'][] = [
            'externalId' => $externalId,
            'error' => $e->getMessage()
        ];
    }

    protected function logResults(string $entityType, array $results)
    {
        $this->log->info("{$entityType} import completed. Results: " . json_encode([
            'total' => $results['total'],
            'created' => $results['created'],
            'updated' => $results['updated'],
            'skipped' => $results['skipped'],
            'error_count' => count($results['errors'])
        ]));
    }
}
