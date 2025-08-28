<?php
namespace Espo\Custom\Services;

use Espo\Core\ORM\EntityManager;
use Espo\Core\Utils\Log;
use Espo\Custom\Classes\Carmine;
use Exception;
use Espo\Core\DataManager;
use Espo\Core\InjectableFactory;

class ImportGeneric
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
        $rebuild = $config['rebuild'] ?? false;

        $this->log->info("Starting {$entityType} import from endpoint: {$endpoint}");

        $carmine = $this->injectable->create(Carmine::class);
        $response = $carmine->importFromCarmine($endpoint);

        $importData = json_decode($response, true);


        return $importData;
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errorMsg = 'Invalid JSON: ' . json_last_error_msg();
            $this->log->error($errorMsg);
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
                $externalId = $this->getNestedValue($item, $fieldMapping['externalId']);
                $nameValue = $this->getNestedValue($item, $fieldMapping['name']);

                if (empty($externalId)) {
                    $results['skipped']++;
                    $transactionManager->commit();
                    continue;
                }

                // Prepare entity data
                $entityData = ['externalId' => $externalId];
                foreach ($fieldMapping as $entityField => $sourceField) {
                    if ($entityField !== 'externalId') {
                        $entityData[$entityField] = $this->getNestedValue($item, $sourceField);
                    }
                }

                // Find or create entity
                $entity = $this->entityManager->getRDBRepository($entityType)
                    ->where(['externalId' => $externalId])
                    ->findOne();

                if ($entity) {
                    $entity->set($entityData);
                    $this->entityManager->saveEntity($entity);
                    $results['updated']++;
                } else {
                    $this->entityManager->createEntity($entityType, $entityData);
                    $results['created']++;
                }

                $transactionManager->commit();

                if (++$processed % $batchSize === 0) {
                    $this->log->info("Processed {$processed}/{$results['total']} {$entityType} records");
                }
            } catch (Exception $e) {
                $transactionManager->rollback();
                $results['errors'][] = [
                    'externalId' => $externalId ?? 'unknown',
                    'error' => $e->getMessage()
                ];
                $this->log->error("{$entityType} import failed: " . $e->getMessage());
            }
        }

        if ($rebuild) {
            $this->dataManager->rebuild();
        }

        return $results;
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

        return $value;
    }
}