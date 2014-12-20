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
     * Options for the getBy call
     * @var array
     */
    protected $options;

    /**
     * Constructs a new external value decorator
     * @param \ride\library\orm\model\Model $model Instance of the model to query
     * @param string $fieldName Name of the field to match
     * @return null
     */
    public function __construct(Model $model, $fieldName, $locale = null) {
        $this->model = $model;
        $this->fieldName = $fieldName;
        $this->locale = $locale;
    }

    /**
     * Sets the base options for the getBy call
     * @param array $options
     * @return null
     */
    public function setOptions(array $options) {
        $this->options = $options;
    }

    /**
     * Decorates the provided value into an entry of the model
     * @param mixed $value Value to match
     * @return mixed Original value if not a string, an entry for the value
     * otherwise
     */
    public function decorate($value) {
        if (!is_string($value) && !is_numeric($value)) {
            return $value;
        }

        $options = $this->options;
        if (!isset($options['filter'])) {
            $options['filter'] = array();
        }

        $options['filter'][$this->fieldName] = $value;

        return $this->model->getBy($options, $this->locale);
    }

}
