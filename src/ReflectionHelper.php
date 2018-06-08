<?php

namespace demmonico\reflection;

/**
 * Class ReflectionHelper
 *
 * Work with classes, functions etc. using Reflection
 *
 * @author: dep
 * @date: 08.07.2016
 * @package demmonico\reflection
 */
class ReflectionHelper
{
    /**
     * Returns array of class methods (can search by prefix)
     * @param $className
     * @param integer|null $filter  Filter the results to include only methods with certain attributes. Any combination of ReflectionMethod::IS_STATIC
     * @param string|null $prefix
     * @return array
     */
    public static function getMethods($className, $filter=null, $prefix=null)
    {
        $reflection = new \ReflectionClass($className);
        $arr = is_null($filter) ? $reflection->getMethods() : $reflection->getMethods($filter);

        $r = [];
        foreach($arr as $i){
            $name = $i->getName();
            if (is_null($prefix) || 0 === $pos=strpos($name, $prefix)){
                $r[] = $name;
            }
        }
        return $r;
    }

    /**
     * Returns array of class constants (can use name prefix)
     *
     * @example
     *  call static::getConstants(get_called_class(), 'STATUS_')
     *  returns array(2) { ["STATUS_DELETED"]=> int(0) ["STATUS_ACTIVE"]=> int(100) }
     *
     * @param string $className
     * @param string $prefix
     * @return array
     */
    public static function getConstants(string $className, ?string $prefix = ''): array
    {
        // get const array
        $reflection = new \ReflectionClass($className);
        $constants = $reflection->getConstants();

        // filter by prefix
        if ('' !== $prefix) {
            $constants = array_filter($constants, function($key) use($prefix) {
                return stripos($key, $prefix) === 0;
            }, ARRAY_FILTER_USE_KEY);
        }

        return $constants;
    }

    /**
     * Returns type of variable value (with/without allowable analyze)
     * @param $var
     * @param array $allowTypesArr
     * @param null $default
     * @return mixed|null|string
     */
    public static function detectVarType($var, $allowTypesArr=[], $default=null)
    {
        $type = gettype($var);
        if (!empty($allowTypesArr)){
            if (false !== $r = array_search(ucfirst($type), $allowTypesArr)) {
                return $r;
            } elseif(!is_null($default)) {
                return $default;
            }
        }
        return $type;
    }
}
