<?php

namespace ride\library\import;

use ride\library\decorator\BooleanDecorator;
use ride\library\decorator\DateFormatDecorator;
use ride\library\decorator\EntryFormatDecorator;
use ride\library\decorator\TimeDecorator;
use ride\library\i18n\translator\Translator;
use ride\library\import\mapper\GenericMapper;
use ride\library\import\provider\SourceProvider;
use ride\library\import\provider\DestinationProvider;
use ride\library\import\provider\orm\OrmSourceProvider;
use ride\library\import\GenericImporter;
use ride\library\orm\definition\field\HasManyField;
use ride\library\orm\definition\field\ModelField;
use ride\library\orm\definition\field\RelationField;
use ride\library\orm\definition\ModelTable;
use ride\library\orm\model\Model;
use ride\library\orm\query\ModelQuery;

/**
 * Exporter for ORM models
 */
class OrmModelExporter {

    /**
     * Format for date fields
     * @var string
     */
    private $dateFormat = 'Y-m-d';

    /**
     * Format for date time fields
     * @var string
     */
    private $dateTimeFormat = 'Y-m-d H:i:s';

    /**
     * Instance of the translator
     * @var \ride\library\i18n\translator\Translator
     */
    private $translator;

    /**
     * Sets the format for date fields
     * @param string $dateFormat
     * @return null
     */
    public function setDateFormat($dateFormat) {
        $this->dateFormat = $dateFormat;
    }

    /**
     * Sets the format for date time fields
     * @param string $dateTimeFormat
     * @return null
     */
    public function setDateTimeFormat($dateTimeFormat) {
        $this->dateTimeFormat = $dateTimeFormat;
    }

    /**
     * Sets the translator to the importer
     * @param \ride\łibrary\i18n\translator\Translator $translator
     * @return null
     */
    public function setTranslator(Translator $translator) {
        $this->translator = $translator;
    }

    /**
     * Performs an export of the provider model query
     * @param \ride\library\orm\query\ModelQuery $modelQuery Query to export
     * @param \ride\library\import\provider\DestinationProvider $destinationProvider
     * Provider for the export destination
     * @return null
     */
    public function export(ModelQuery $modelQuery, DestinationProvider $destinationProvider) {
        $model = null;
        $sourceProvider = $this->getSourceProvider($modelQuery, $model);
        $importer = $this->getImporter($sourceProvider, $destinationProvider);

        $importer->import();
    }

    /**
     * Gets the source provider for the provided model query
     * @param \ride\library\orm\query\ModelQuery $modelQuery
     * @param \ride\library\orm\model\Model $model
     * @return \ride\library\import\provider\SourceProvider
     */
    protected function getSourceProvider(ModelQuery $modelQuery, Model &$model = null) {
        $model = $modelQuery->getModel();

        $sourceProvider = new OrmSourceProvider($model);
        $sourceProvider->setQuery($modelQuery);

        return $sourceProvider;
    }

    /**
     * Gets the importer for the provided providers
     * @param \ride\library\import\provider\SourceProvider $sourceProvider
     * @param \ride\library\import\provider\DestinationProvider $destinationProvider
     * @return \ride\library\import\Importer
     */
    protected function getImporter(SourceProvider $sourceProvider, DestinationProvider $destinationProvider) {
        $mapper = $this->mapModel($sourceProvider->getModel(), $sourceProvider, $destinationProvider);

        $importer = new GenericImporter();
        $importer->setSourceProvider($sourceProvider);
        $importer->setDestinationProvider($destinationProvider);
        $importer->addMapper($mapper);

        return $importer;
    }

    /**
     * Maps teh provided model from the source provider to the destination
     * provider
     * @param \ride\library\orm\model\Model $model
     * @param \ride\library\import\provider\SourceProvider $sourceProvider
     * @param \ride\library\import\provider\DestinationProvider $destinationProvider
     * @return \ride\library\import\mapper\Mapper
     */
    protected function mapModel(Model $model, SourceProvider $sourceProvider, DestinationProvider $destinationProvider) {
        $meta = $model->getMeta();
        $orm = $model->getOrmManager();
        $entryFormatter = $orm->getEntryFormatter();
        $decorators = $this->getDecorators();

        $mapper = new GenericMapper();

        // retrieve fields to export and sort in requested order
        $exportFields = $meta->getOption('scaffold.export');
        $exportFields = $this->getExportFields($exportFields);
        if (!$exportFields) {
            $exportFields = true;
        }

        $fields = $meta->getFields();
        $fields = $this->getFields($fields, $exportFields, true);

        // map id
        $columnIndex = 1;
        $destinationProvider->setColumnName($columnIndex++, '#');
        $mapper->mapColumn(ModelTable::PRIMARY_KEY, '#');

        // map fields
        foreach ($fields as $fieldName => $field) {
            $columnName = $this->getColumnName($field);

            if ($field instanceof RelationField) {
                // hasOne or belongsTo
                $relationModelName = $field->getRelationModelName();
                $relationModel = $orm->getModel($relationModelName);

                $exportFields = $field->getOption('scaffold.export', true);
                $exportFields = $this->getExportFields($exportFields);

                $relationFields = $relationModel->getMeta()->getFields();
                $relationFields = $this->getFields($relationFields, $exportFields, false);

                foreach ($relationFields as $relationFieldName => $relationField) {
                    if ($relationFieldName === ModelTable::PRIMARY_KEY) {
                        $relationColumnName = $columnName;
                    } else {
                        $relationColumnName = $this->getColumnName($relationField);
                        $relationColumnName .= ' (' . $columnName . ')';
                    }

                    $destinationProvider->setColumnName($columnIndex, $relationColumnName);

                    $mapper->mapColumn($fieldName, $relationColumnName);

                    if ($relationField instanceof RelationField) {
                        $mapper->addDecorator($relationColumnName, new EntryFormatDecorator($entryFormatter, '{' . $relationFieldName . '}'));
                        $mapper->addDecorator($relationColumnName, new EntryFormatDecorator($entryFormatter, '{' . ModelTable::PRIMARY_KEY . '}'));
                    } else {
                        $mapper->addDecorator($relationColumnName, new EntryFormatDecorator($entryFormatter, '{' . $relationFieldName . '}'));
                        if (isset($decorators[$relationField->getType()])) {
                            $mapper->addDecorator($relationColumnName, $decorators[$relationField->getType()]);
                        }
                    }

                    $columnIndex++;
                }
            } else {
                // regular property
                $destinationProvider->setColumnName($columnIndex, $columnName);

                $mapper->mapColumn($fieldName, $columnName);
                if (isset($decorators[$field->getType()])) {
                    $mapper->addDecorator($columnName, $decorators[$field->getType()]);
                }

                $columnIndex++;
            }
        }

        return $mapper;
    }

    /**
     * Gets the fields to export
     * @param array $relationFields All the fields of the relation
     * @param boolean|array $exportFields True or an array with fields to export
     * @return array Array with the name of the field as key and an instance of
     * ModelField as value
     */
    protected function getFields(array $fields, $exportFields, $includeDefault) {
        $result = array();

        if ($exportFields === true) {
            foreach ($fields as $fieldName => $field) {
                if ($field->getOption('scaffold.export')) {
                    $result[$fieldName] = $field;
                }
            }
        } elseif (is_array($exportFields)) {
            foreach ($exportFields as $exportFieldName) {
                if (isset($fields[$exportFieldName])) {
                    $result[$exportFieldName] = $fields[$exportFieldName];
                }
            }
        }

        if (!$result && $includeDefault) {
            $result = $fields;

            unset($result[ModelTable::PRIMARY_KEY]);
        }

        return $result;
    }

    /**
     * Gets the field names of the fields to export
     * @param string $exportOption Value for the export option of the field
     * @return boolean|array True to let the relation model decide, array with
     * the name of the field as key and value
     */
    protected function getExportFields($exportOption) {
        if ($exportOption === '1' || strtolower($exportOption) === 'true') {
            return true;
        }

        $tmpExportFields = explode(',', $exportOption);

        $exportFields = array();
        foreach ($tmpExportFields as $exportField) {
            $exportField = trim($exportField);

            $exportFields[$exportField] = $exportField;
        }

        return $exportFields;
    }

    /**
     * Gets the human friendly column name of the provided field
     * @param \ride\library\orm\definition\field\ModelField $field
     * @return string
     */
    protected function getColumnName(ModelField $field) {
        $columnName = $field->getName();

        if ($this->translator) {
            $translationKey = $field->getOption('label.export');
            if ($translationKey) {
                $columnName = $this->translator->translate($translationKey);
            } else {
                $translationKey = $field->getOption('label.name');
                if ($translationKey) {
                    $columnName = $this->translator->translate($translationKey);
                }
            }
        }

        return $columnName;
    }

    /**
     * Gets the available decorators for the different property fields
     * @return array Array with the field type as key and a decorator as value
     */
    protected function getDecorators() {
        $booleanDecorator = new BooleanDecorator();
        if ($this->translator) {
            $booleanDecorator->setLabels($this->translator->translate('label.yes'), $this->translator->translate('label.no'));
        }

        $dateDecorator = new DateFormatDecorator();
        $dateDecorator->setDateFormat($this->dateFormat);

        $dateTimeDecorator = new DateFormatDecorator();
        $dateTimeDecorator->setDateFormat($this->dateTimeFormat);

        $timeDecorator = new TimeDecorator();

        return array(
            'boolean' => $booleanDecorator,
            'date' => $dateDecorator,
            'datetime' => $dateTimeDecorator,
            'time' => $timeDecorator,
        );
    }

}
