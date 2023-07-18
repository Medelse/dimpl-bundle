<?php

namespace Medelse\DimplBundle\Tool;

class ArrayFormatter
{
    /**
     * Unset the keys if value is null or an empty string
     */
    public static function removeNullValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::removeNullValues($value);
            }

            if (is_null($value) || (is_string($value) && '' === $value)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
