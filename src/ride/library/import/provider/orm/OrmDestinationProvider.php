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
     * Performs preparation tasks of the import
     * @return null
     */
    public function preImport(Importer $importer) {
        $this->connection = $this->model->getOrmManager()->getConnection();
        $this->isTransactionStarted = $this->connection->beginTransaction();
    }

    /**
     * Imports a row into this destination
     * @param array $row Array with the name of the column as key and the
     * value to import as value
     */
    public function setRow(array $row) {
        try {
            $entry = $this->model->createEntry();

            foreach ($this->columnNames as $columnName) {
                if (isset($row[$columnName])) {
                    $this->reflectionHelper->setProperty($entry, $columnName, $row[$columnName]);
                }
            }
var_export($entry);
            $this->model->save($entry);
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
