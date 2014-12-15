<?php

namespace ride\library\import\provider\orm;

use ride\library\import\provider\DestinationProvider;
use ride\library\import\Importer;

use \Exception;

/**
 * Import source provider of a ORM model
 */
class OrmDestinationProvider extends AbstractOrmProvider implements DestinationProvider {

    /**
     * Name of the source column which holds the id in the external resource
     * @var string
     */
    protected $externalId;

    /**
     * Map with the external id as key and the internal id as value
     * @var array
     */
    protected $externalIdMap;

    /**
     * Instance of the database connection
     * @var \ride\library\database\driver\Driver
     */
    protected $connection;

    /**
     * Flag to see if this import started the transaction
     * @var boolean
     */
    protected $isTransactionStarted;

    /**
     * Sets the source column which holds the id in the external resource
     * @param string $externalId Name of the source column
     * @return null
     */
    public function setExternalId($externalId) {
        $this->externalId = $externalId;
    }

    /**
     * Gets the map of external ids with the internal id
     * @return array
     */
    public function getExternalIdMap() {
        return $this->externalIdMap;
    }

    /**
     * Performs preparation tasks of the import
     * @return null
     */
    public function preImport(Importer $importer) {
        $this->connection = $this->model->getOrmManager()->getConnection();
        $this->isTransactionStarted = $this->connection->beginTransaction();

        $this->externalIdMap = array();
    }

    /**
     * Imports a row into this destination
     * @param array $row Array with the name of the column as key and the
     * value to import as value
     */
    public function setRow(array $row) {
        try {
            $entry = null;
            $externalId = null;

            if ($this->externalId && isset($row[$this->externalId])) {
                $externalId = $row[$this->externalId];
                $entry = $this->model->getById($externalId, $this->locale, true);
            }

            if (!$entry) {
                $entry = $this->model->createEntry();

                if ($this->model->getMeta()->isLocalized()) {
                    $entry->setLocale($this->locale);
                }
            }

            foreach ($this->columnNames as $columnName) {
                if (isset($row[$columnName])) {
                    $this->reflectionHelper->setProperty($entry, $columnName, $row[$columnName]);
                }
            }

            $this->model->save($entry);

            if ($externalId) {
                $this->externalIdMap[$externalId] = $entry->id;
            }
        } catch (Exception $exception) {
            if ($this->isTransactionStarted) {
                $this->connection->rollbackTransaction();
            }

            throw $exception;
        }
    }

    /**
     * Performs finishing tasks of the import
     * @return null
     */
    public function postImport() {
        if ($this->isTransactionStarted) {
            $this->connection->commitTransaction();
        }
    }

}
