<?php

namespace ride\library\import\provider\orm;

/**
 * Provider for the map to link data between source and destination provider
 */
interface IdMapProvider {

    /**
     * Gets a map to link the data of source and destination provider
     * @return array Array with the id in the source provider with the id of the
     * destination provider as value
     */
    public function getIdMap();

}
