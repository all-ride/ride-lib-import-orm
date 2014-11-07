<?php

namespace ride\library\decorator;

use ride\library\import\provider\orm\OrmDestinationProvider;

/**
 * Decorator to create proxy values for a import destination provider. It should
 * be used in a mapper after the provided destination provider has been
 * imported. You can then use this decorator on the proceeding import for the id
 * of the external entry. It wel then convert the external id into a entry proxy.
 */
class ExternalIdDecorator implements Decorator {

    /**
     * Constructs a new external id decorator
     * @param \ride\library\import\provider\orm\OrmDestinationProvider $destinationProvider
     * @return null
     */
    public function __construct(OrmDestinationProvider $destinationProvider) {
        $this->destinationProvider = $destinationProvider;
    }

    /**
     * Decorates the provided value as a entry proxy
     * @param mixed $value Value to decorate
     * @return mixed Entry proxy for the destination provider of possible,
     * original value otherwise
     */
    public function decorate($value) {
        $externalIdMap = $this->destinationProvider->getExternalIdMap();
        if (!isset($externalIdMap[$value])) {
            return $value;
        }

        return $this->destinationProvider->getModel()->createProxy($externalIdMap[$value]);
    }

}
