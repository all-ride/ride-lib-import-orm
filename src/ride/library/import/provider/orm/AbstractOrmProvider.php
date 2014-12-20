<?php

namespace ride\library\import\provider\orm;

use ride\library\import\provider\Provider;
use ride\library\import\Importer;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\Model;
use ride\library\reflection\ReflectionHelper;

/**
 * Abstract import provider for a ORM model
 */
abstract class AbstractOrmProvider implements Provider {

    /**
     * Instance of the model
     * @var \ride\library\orm\model\Model
     */
    protected $model;

    /**
     * Instance of the reflection helper
     * @var \ride\library\reflection\ReflectionHelper
     */
    protected $reflectionHelper;

    /**
     * Code of the locale
     * @param string
     */
    protected $locale;

    /**
     * Array with the name of the column as key and value
     * @var array
     */
    protected $columnNames;

    /**
     * Constructs a new orm provider
     * @param \ride\library\orm\model\Model $model
     * @return null
     */
    public function __construct(Model $model, ReflectionHelper $reflectionHelper) {
        $this->model = $model;
        $this->reflectionHelper = $reflectionHelper;
        $this->columnNames = array(ModelTable::PRIMARY_KEY => ModelTable::PRIMARY_KEY);

        $fields = $this->model->getMeta()->getFields();
        foreach ($fields as $fieldName => $field) {
            $this->columnNames[$fieldName] = $fieldName;
        }
    }

    /**
     * Gets the model of this provider
     * @return \ride\library\orm\model\Model
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Sets the locale for the entries
     * @param string|null $locale Code of the locale
     * @return null
     */
    public function setLocale($locale) {
        $this->locale = $locale;
    }

    /**
     * Gets the locale of the entries
     * @return string|null Code of the locale
     */
    public function getLocale() {
        return $this->locale;
    }

    /**
     * Gets the available column names for this provider
     * @return array Array with the name of the column as key and as value
     */
    public function getColumnNames() {
        return $this->columnNames;
    }

}
