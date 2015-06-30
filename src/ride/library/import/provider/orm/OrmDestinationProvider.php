<?php

namespace ride\library\import\provider\orm;

use ride\library\import\provider\orm\populator\EntryPopulator;
use ride\library\import\provider\DestinationProvider;
use ride\library\import\Importer;
use ride\library\orm\definition\ModelTable;
use ride\library\validation\exception\ValidationException;

use \Exception;

/**
 * Import destintation provider of a ORM model
 */
class OrmDestinationProvider extends AbstractOrmProvider implements DestinationProvider, IdMapProvider {

    /**
     * Name of the source column which holds the id in the source provider
     * @var string
     */
    protected $sourceId;

    /**
     * Flag to see existing entries should be used instead of automatically
     * import as new entries
     * @var boolean
     */
    protected $sourceLookup;

    /**
     * Map with the source id as key and the model entry id as value, used to
     * store the link between source and destination
     * @var array
     */
    protected $idMap = array();

    /**
     * Destination provider to initialize the id map
     * @var IdMapProvider
     */
    protected $idMapProvider;

    /**
     * Flag to see if validation exceptions should be ignored
     * @var boolean
     */
    protected $ignoreValidationException;

    /**
     * Flag to see if existing entries should be skipped
     * @var boolean
     */
    protected $skipExistingEntries;

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
     * Sets the source column which holds the id in the source provider
     * @param string $sourceId Name of the source column
     * @return null
     */
    public function setSourceId($sourceId) {
        $this->sourceId = $sourceId;
    }

    /**
     * Gets the source column which holds the id in the source provider
     * @return string Name of the source column
     */
    public function getSourceId() {
        return $this->sourceId;
    }

    /**
     * Sets wheter an existing entry should be queried instead of automatically
     * insert a new entry. This query uses the sourceId as column name.
     * @param boolean $sourceLookup
     * @return null
     */
    public function setSourceLookup($sourceLookup) {
        $this->sourceLookup = $sourceLookup;
    }

    /**
     * Sets the id map to resolve existing entries. Source id needs to be set
     * to have an effect of this call.
     * @param array $idMap Array with the id in the source provider as key
     * and the id of the model entry as value
     * @return null
     */
    public function setIdMap(array $idMap) {
        $this->idMap = $idMap;
    }

    /**
     * Gets the map of source ids with the id of the model entry
     * @return array Array with the id in the source provider as key and the id
     * of the model entry as value
     */
    public function getIdMap() {
        return $this->idMap;
    }

    /**
     * Sets the provider for the id map
     * @param IdMapProvider $provider Provider to retrieve the id map from
     * before running the import
     * @return null
     */
    public function setIdMapProvider(IdMapProvider $provider = null) {
        $this->idMapProvider = $provider;
    }

    /**
     * Gets the provider for the id map
     * @return OrmDestinationProvider Provider to retrieve the id map from
     * before running the import
     */
    public function getIdMapProvider() {
        return $this->idMapProvider;
    }

    /**
     * Sets the flag to ignore validation exceptions
     * @param boolean $ignoreValidationException
     * @return null
     */
    public function setIgnoreValidationException($ignoreValidationException) {
        $this->ignoreValidationException = $ignoreValidationException;
    }

    /**
     * Sets the flag to skip existing entries
     * @param boolean $skipExistingEntries
     * @return null
     */
    public function setSkipExistingEntries($skipExistingEntries) {
        $this->skipExistingEntries = $skipExistingEntries;
    }

    /**
     * Sets the entry populator
     * @param \ride\library\import\provider\orm\populator\EntryPopulator $entryPopulator
     */
    public function setEntryPopulator(EntryPopulator $entryPopulator) {
        $this->entryPopulator = $entryPopulator;
    }

    /**
     * Gets the entry populator
     * @return \ride\library\import\provider\orm\populator\EntryPopulator
     */
    public function getEntryPopulator() {
        if (!$this->entryPopulator) {
            $this->entryPopulator = new GenericEntryPopulator($this->model->getReflectionHelper());
        }

        return $this->entryPopulator;
    }

    /**
     * Performs preparation tasks of the import
     * @return null
     */
    public function preImport(Importer $importer) {
        $this->connection = $this->model->getOrmManager()->getConnection();
        $this->isTransactionStarted = $this->connection->beginTransaction();

        if ($this->idMapProvider) {
            $this->idMap = $this->idMapProvider->getIdMap();
        }
    }

    /**
     * Imports a row into this destination
     * @param array $row Array with the name of the column as key and the
     * value to import as value
     */
    public function setRow(array $row) {
        try {
            $sourceId = null;
            $entry = null;

            // check for a link between source and destination
            if ($this->sourceId && isset($row[$this->sourceId])) {
                // source id set
                $sourceId = $row[$this->sourceId];

                if ($this->idMap && isset($this->idMap[$sourceId])) {
                    // lookup entry based on the id map
                    $entryId = $this->idMap[$sourceId];
                    $entry = $this->model->getById($entryId, $this->locale, true);
                } elseif ($this->sourceLookup) {
                    $entry = $this->model->getBy(array('filter' => array($this->sourceId => $sourceId)), $this->locale, true);
                }
            }

            if ($this->skipExistingEntries && $entry) {
                // skip existing entries, do nothing and go back
                return;
            }

            if (!$entry) {
                // no entry to lookup or not found, create a new one
                $entry = $this->model->createEntry();
            }

            if ($this->locale && $this->model->getMeta()->isLocalized()) {
                // locale set and the model is localized
                $entry->setLocale($this->locale);
            }

            // populate properties of the entry
            $this->getEntryPopulator()->populateEntry($this->columnNames, $entry, $row);

            // save the entry
            $this->model->save($entry);

            // update the id map
            if ($sourceId) {
                $this->idMap[$sourceId] = $entry->getId();
            }
        } catch (Exception $exception) {
            $ignore = false;

            if ($this->ignoreValidationException && $exception instanceof ValidationException) {
                $ignore = true;
            }

            if (!$ignore) {
                if ($this->isTransactionStarted) {
                    $this->connection->rollbackTransaction();
                }

                throw $exception;
            }
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
