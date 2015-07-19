<?php
namespace Bro\core;

class DataHelper
{
    public static function filter($source, $fieldList, $fieldTypes = array())
    {
        $result = array();
        foreach ($fieldList as $sourceField => $targetField) {
            if (isset($source[$sourceField])) {
                $result[$targetField] = $source[$sourceField];
            } else {
                return false;
            }
        }
        foreach ($fieldTypes as $fieldName => $fieldType) {
            if ($fieldType === FILTER_VALIDATE_BOOLEAN) {
                $result[$fieldName] = filter_var($result[$fieldName], $fieldType, FILTER_NULL_ON_FAILURE);
                if ($result[$fieldName] === null) {
                    return false;
                }
            } else {
                $result[$fieldName] = filter_var($result[$fieldName], $fieldType);
                if ($result[$fieldName] === false) {
                    return false;
                }
            }
        }
        return $result;
    }
}