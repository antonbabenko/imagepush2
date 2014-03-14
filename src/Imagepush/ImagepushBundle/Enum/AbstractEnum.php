<?php

namespace Imagepush\ImagepushBundle\Enum;

/**
 * Base class for Enums
 */
abstract class AbstractEnum
{
    /**
     * @var array
     */
    protected static $_list = array();

    /**
     * @param string $key
     *
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public static function get($key)
    {
        if (false == static::containsKey($key)) {
            throw new \InvalidArgumentException(sprintf(
                'Given Argument "%s" does not exist or invalid. Available are "%s".', $key, static::implode()
            ));
        }

        return static::$_list[$key];
    }

    /**
     * @return array
     */
    public static function getValues()
    {
        return array_values(static::$_list);
    }

    /**
     * @return array
     */
    public static function getKeys()
    {
        return array_keys(static::$_list);
    }

    /**
     * @return array
     */
    public static function toArray()
    {
        return static::$_list;
    }

    /**
     * @param string|int $key
     *
     * @return bool
     */
    public static function containsKey($key)
    {
        return array_key_exists($key, static::$_list);
    }

    /**
     * @param string $separator
     *
     * @return string
     */
    public static function implode($separator = ',')
    {
        return implode($separator, array_keys(static::$_list));
    }
}
