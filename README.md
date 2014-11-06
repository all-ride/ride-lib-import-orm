# Ride: Import Library

ORM providers of the import library of the PHP Ride framework.

## Code Sample

Check this code sample to see how to use these providers:

    <?php

    use ride\library\decorator\BooleanDecorator;
    use ride\library\import\mapper\GenericMapper;
    use ride\library\import\provider\orm\OrmSourceProvider;
    use ride\library\import\provider\orm\OrmDestinationProvider;
    use ride\library\import\GenericImporter;
    use ride\library\orm\OrmManager;
    use ride\library\reflection\ReflectionHelper;

    function importModel(OrmManager $orm, ReflectionHelper $reflectionHelper) {
        $sourceProvider = new OrmSourceProvider($orm->getModel('Source'), $reflectionHelper);
        $destinationProvider = new OrmSourceProvider($orm->getModel('Destination'), $reflectionHelper);

        // create a mapping to translate values from the source to the destination
        $mapper = new GenericMapper();
        $mapper->mapColumn(array('name', 'firstname'), 'fullName');
        // glue street, number and box together; use a space between street and number, then use a slash to add the box
        $mapper->mapColumn(array('street', 'number', 'box'), 'address', array(' ', '/'));
        $mapper->mapColumn('postalCode', 'postalCode');
        $mapper->mapColumn('city', 'city');
        $mapper->mapColumn('subscribe_newsletter', 'isNewsletter');
        // you can add decorators to process the resulting value
        $mapper->addDecorator('isNewsletter', new BooleanDecorator());

        // initialize importer with providers and mapper
        $importer = new GenericImporter();
        $importer->setSourceProvider($sourceProvider);
        $importer->setDestinationProvider($destinationProvider);
        $importer->addMapper($mapper);

        $importer->import();
    }
