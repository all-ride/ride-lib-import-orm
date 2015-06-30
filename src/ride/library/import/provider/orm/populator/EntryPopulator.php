<?php

namespace ride\library\import\provider\orm\populator;

/**
 * Interface for the population of an entry
 */
interface EntryPopulator {

    /**
     * Populates the entry with the provided data
     * @param array $columnNames
     * @param mixed $entry
     * @param array $data
     * @return null
     */
    public function populateEntry($columnNames, $entry, $data);

}
