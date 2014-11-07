<?php

namespace ride\library\decorator;

use ride\library\orm\OrmManager;

use ride\web\orm\taxonomy\OrmTagHandler;

/**
 * Decorator which finds TaxonomyTerm entries for given string in a given vocabulary.
 */
class TaxonomyDecorator implements Decorator {

    /**
     * Constructs a new external id decorator
     * @param \ride\library\orm\OrmManager $ormManager
     * @param string $vocabulary Id of the vocabulary
     * @param string $delimiter Delimiter on which to explode the values
     * @return null
     */
    public function __construct(OrmManager $ormManager, $vocabulary, $delimiter = ',') {
        $this->ormTagHandler =  new OrmTagHandler($ormManager, $vocabulary);
        $this->delimiter = $delimiter;
    }

    /**
     * Decorates the provided value as a entry proxy
     * @param mixed $values Values to decorate
     * @return array Entry proxy for the destination provider of possible,
     */
    public function decorate($value) {
        if (!is_string($value)) {
            return $value;
        }

        $values = explode($this->delimiter, $value);
        $tags = $this->ormTagHandler->processTags($values);

        return $tags;
    }

}
