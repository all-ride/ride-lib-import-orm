<?php

namespace ride\library\import\provider\orm;

use ride\library\import\exception\ImportException;
use ride\library\import\provider\SourceProvider;
use ride\library\import\Importer;
use ride\library\orm\query\ModelQuery;

/**
 * Import source provider of a ORM model
 */
class OrmSourceProvider extends AbstractOrmProvider implements SourceProvider {

    /**
     * Instance of the query which will be used to fetch the source entries
     * @var \ride\library\orm\query\ModelQuery
     */
    protected $query;

    /**
     * Sets the query which will be used to fetch the source entries
     * @param \ride\library\orm\query\ModelQuery $query $query
     * @return null
     */
    public function setQuery(ModelQuery $query) {
        if ($query->getModel()->getName() != $this->model->getName()) {
            throw new ImportException('Could not set model query for this source provider: query is not for model ' . $this->model->getName());
        }

        $this->query = $query;
    }

    /**
     * Gets the query which will be used to fetch the source entries
     * @return \ride\library\orm\query\ModelQuery
     */
    public function getQuery() {
        if ($this->query === null) {
            $this->query = $this->model->createQuery($this->locale);
        }

        return $this->query;
    }

    /**
     * Performs preparation tasks of the import
     * @return null
     */
    public function preImport(Importer $importer) {
        $this->result = $this->getQuery()->query();
        reset($this->result);
    }

    /**
     * Gets the next row from this destination
     * @return array|null $data Array with the name of the column as key and the
     * value to import as value. Null is returned when all rows are processed.
     */
    public function getRow() {
        $row = each($this->result);
        if ($row === false) {
            return null;
        }

        $row = $row['value'];
        $reflectionHelper = $this->model->getReflectionHelper();

        $result = array();
        foreach ($this->columnNames as $columnName) {
            $result[$columnName] = $reflectionHelper->getProperty($row, $columnName);
        }

        return $result;
    }

    /**
     * Performs finishing tasks of the import
     * @return null
     */
    public function postImport() {
        $this->query = null;
    }

}
