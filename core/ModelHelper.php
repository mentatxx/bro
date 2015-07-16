<?php
namespace Bro\core;


class ModelHelper
{
    /**
     * Assigns data to model
     * Returns true on fields filled, false if some are missing
     *
     * @param $model
     * @param $data
     * @return bool
     */
    public static function assign(&$model, $data)
    {
        foreach($model as $key => $value) {
            if (isset($data[$key])) {
                $model[$key] = $data[$key];
            } else {
                return false;
            }
        }
        return true;
    }
}