<?php

namespace ride\library\import\provider\orm\populator;

use ride\library\orm\definition\ModelTable;
use ride\library\reflection\ReflectionHelper;

/**
 * Generic implementation for the population of an entry
 */
class GenericEntryPopulator implements EntryPopulator {

    /**
     * Instance of the reflection helper
     * @var \ride\library\reflection\ReflectionHelper
     */
    private $reflectionHelper;

    /**
     * Constructs a new entry populator
     * @param \ride\library\reflection\ReflectionHelper $reflectionHelper
     */
    public function __construct(ReflectionHelper $reflectionHelper) {
        $this->reflectionHelper = $reflectionHelper;
    }

    /**
     * Populates the entry with the provided data
     * @param array $columnNames
     * @param mixed $entry
     * @param array $data
     * @return null
     */
    public function populateEntry($columnNames, $entry, $data) {
        foreach ($columnNames as $columnName) {
            if ($columnName == ModelTable::PRIMARY_KEY) {
                continue;
            }

            if (isset($data[$columnName])) {
                $this->reflectionHelper->setProperty($entry, $columnName, $data[$columnName]);
            }
        }
    }

}
