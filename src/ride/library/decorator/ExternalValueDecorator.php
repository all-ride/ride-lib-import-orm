<?php

namespace ride\library\decorator;

use ride\library\orm\model\Model;

/**
 * Decorate to a entry based on a exact match of a field
 */
class ExternalValueDecorator implements Decorator {

    /**
     * Instance of the model to query
     * @var \ride\library\orm\model\Model
     */
    protected $model;

    /**
     * Name of the model
     * @var string
     */
    protected $fieldName;

    /**
     * Constructs a new external value decorator
     * @param \ride\library\orm\model\Model $model Instance of the model to query
     * @param string $fieldName Name of the field to match
     * @return null
     */
    public function __construct(Model $model, $fieldName) {
        $this->model = $model;
        $this->fieldName = $fieldName;
    }

    /**
     * Decorates the provided value into an entry of the model
     * @param mixed $value Value to match
     * @return mixed Original value if not a string, an entry for the value
     * otherwise
     */
    public function decorate($value) {
        if (!is_string($value)) {
            return $value;
        }

        return $this->model->getBy(array('filter' => array($this->fieldName => $value)));
    }

}
