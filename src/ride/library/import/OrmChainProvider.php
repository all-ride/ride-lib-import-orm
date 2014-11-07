<?php

namespace ride\library\import;

use ride\library\orm\OrmManager;

use \Exception;

/**
 * Orm chain provider with transaction over the full chain
 */
class OrmChainImporter extends ChainImporter {

    /**
     * Instance of the ORM manager
     * @var \ride\library\orm\OrmManager
     */
    protected $orm;

    /**
     * Database connection used by the ORM manager
     * @var \ride\library\database\driver\Driver
     */
    protected $connection;

    /**
     * Flag to see if this importer started the transaction
     * @var boolean
     */
    protected $isTransactionStarted;

    /**
     * Constructs a new ORM chain importer
     * @param \ride\library\orm\OrmManager $orm
     * @return null
     */
    public function __construct(OrmManager $orm) {
        $this->orm = $orm;
    }

    /**
     * Executes the import chain
     * @return null
     */
    public function import() {
        try {
            parent::import();
        } catch (Exception $exception) {
            if ($this->isTransactionStarted) {
                $this->connection->rollbackTransaction();
            }
        }
    }

    /**
     * Hook before importing
     * @return null
     */
    public function preImport() {
        $this->connection = $this->orm->getConnection();
        $this->isTransactionStarted = $this->connection->beginTransaction();
    }

    /**
     * Hook after importing
     * @return null
     */
    public function postImport() {
        if ($this->isTransactionStarted) {
            $this->connection->commitTransaction();
        }
    }

}
