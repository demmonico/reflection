<?php

namespace demmonico\reflection;

/**
 * Trait ConstantTrait
 *
 * Handle magic calls to get array of class's constant labels or single constant label by it value
 *
 * @use
 *
 *  SIMPLE USAGE
 * ```````````````
 *  class Simple {
 *      use ConstantTrait;
 *
 *      const GROUP_TEST_ONEWORD = 1;
 *      const GROUP_TEST_TWO_WORDS = 2;
 *      const GROUP_TEST_COM_plex_LAbeL = 3;
 *
 *      public function test() { return $this->constGroupTest(); }
 *      public function testStatic() { return static::constGroupTest(); }
 *      public function testSingleLabel() { return $this->constGroupTest(static::GROUP_TEST_TWO_WORDS); }
 *
 *      public function testInvalidPrefix() { return $this->invalidGroupTest(); }
 *      public function testInvalidConstGroup() { return $this->constInvalidGroup(); }
 *      public function testInvalidConstValue() { return $this->constGroupTest(1000); }
 * }
 *
 * $simple = new Simple;
 * $simple->test();     or      $simple->testStatic();      or      Simple::testStatic();
 *      returns array(3) { [1]=> string(7) "Oneword" [2]=> string(10) "Two Words" [3]=> string(16) "COM plex LAbe L" }
 * $simple->testSingleLabel();
 *      returns string(10) "Two Words"
 * $simple->testInvalidPrefix();    or      $simple->testInvalidConstGroup();
 *      if no parent::__call and/or parent::__callStatic exists then
 *      throws Error "Call to undefined method Simple::invalidGroupTest()"      or      "testInvalidConstGroup"
 *      else
 *      throws Error from parent
 * $simple->testInvalidConstValue();
 *      Invalid argument '1000' while calling Simple::constGroupTest(1000)
 *
 * ```````````````
 * as a summary - return array:
 * [    1 => "Oneword",
 *      2 => "Two Words",
 *      3 => "COM plex LAbe L"  ]
 *
 * ```````````````
 *
 *
 * CUSTOMIZATION
 * ```````````````
 * - change prefix for the const getter
 *  define somewhere before call const getter:
 *      static::$constMagicGetterPrefix = 'get';
 *      ...
 *      Simple::getGroupTest();
 *
 *  Note: attempt to override static property like below will trigger an error
 *      protected static $constMagicGetterPrefix = 'get';
 *
 * ```````````````
 * - add/override constant label for get complex label formatting
 *  class Simple {
 *      ...
 *      protected static $constMagicLabels = ['GroupTest' => [3 => 'COMP-lex-LaBEL', 0 => 'Extra label']];
 *      ... or ...
 *      protected static constMagicLabels() { return ['GroupTest' => [3 => 'COMP-lex-LaBEL', 0 => 'Extra label']]; }
 *  }
 *
 *  $simple->test();
 *      returns array(4) { [1]=> string(7) "Oneword" [2]=> string(10) "Two Words" [3]=> string(14) "COMP-lex-LaBEL" [0]=> string(11) "Extra label" }
 *
 *  Note: you could change name of property/method of label customization via set before call const getter
 *      static::$constMagicCustomLabels = 'renamedConstMagicLabels';
 *
 * ```````````````
 * as a summary - return array:
 * [    1 => "Oneword",
 *      2 => "Two Words",
 *      3 => "COMP-lex-LaBEL",
 *      0 => "Extra label"      ]
 *
 * ```````````````
 *
 *
 * @author: dep
 * @date: 08.06.2018
 * @package common\traits\reflection
 */
trait ConstantTrait
{
    /**
     * @var string
     */
    protected static $constMagicGetterPrefix = 'const';
    /**
     * @var string
     */
    protected static $constMagicCustomLabels = 'constMagicLabels';

    /**
     * @var array
     */
    private static $cacheConstMethod = [];


    /**
     * @param $name
     * @param $arguments
     * @return array|bool|string
     */
    public static function __callStatic($name, $arguments)
    {
        try
        {
            return self::constMagicGetterConstants($name, $arguments);
        }
        catch (\BadMethodCallException $e)
        {
            // save backward compatibility
            try {
                return parent::__callStatic($name, $arguments);
            }
            catch (\Error $e)
            {
                // emulate native behavior with backward compatibility
                $msg = $e->getMessage();

                // filter parent errors
                if ('Cannot access parent:: when current class scope has no parent' === $msg
                    || preg_match('/^Call to undefined method (.)*::__callStatic\(\)$/', $msg)
                ) {
                    // no parent calls
                    throw new \Error('Call to undefined method '.get_called_class()."::$name()");
                } else {
                    // re-throw parent error
                    throw $e;
                }
            }
        }
    }


    /**
     * @param $name
     * @param $arguments
     * @return array|string
     * @throws \Exception
     */
    public function __call($name, $arguments)
    {
        try
        {
            return self::constMagicGetterConstants($name, $arguments);
        }
        catch (\BadMethodCallException $e)
        {
            // save backward compatibility
            try {
                return parent::__call($name, $arguments);
            }
            catch (\Error $e)
            {
                // emulate native behavior with backward compatibility
                $msg = $e->getMessage();

                // filter parent errors
                if ('Cannot access parent:: when current class scope has no parent' === $msg
                    || preg_match('/^Call to undefined method (.)*::__call\(\)$/', $msg)
                ) {
                    // no parent calls
                    throw new \Error('Call to undefined method '.get_called_class()."::$name()");
                } else {
                    // re-throw parent error
                    throw $e;
                }
            }
        }
    }


    /**
     * @param $name
     * @param $arguments
     * @return array
     */
    private static function constMagicGetterConstants($name, $arguments)
    {
        $className = get_called_class();

        // match method prefix
        if (0 !== strpos($name, $prefix = static::$constMagicGetterPrefix)) {
            throw new \BadMethodCallException("Invalid prefix (needed '$prefix') while calling $className::$name()");
        }

        // cut off prefix
        $methodSuffix = substr($name, strlen(static::$constMagicGetterPrefix));
        // transform CamelCaseMethodSuffix to CONST_PREFIX_
        $constPrefix = strtoupper(
                trim( preg_replace('/(?<![A-Z])[A-Z]/', '_\0', $methodSuffix), '_')
            ) . '_';

        // use cache if exists and no arguments
        if ([] === $arguments && isset(self::$cacheConstMethod[$constPrefix])) {
            $constArr = self::$cacheConstMethod[$constPrefix];
        }
        // else load from class
        else {
            // get const array
            $constArr = ReflectionHelper::getConstants($className, $constPrefix);
            if (empty($constArr)) {
                throw new \BadMethodCallException("No constants was found by prefix '$prefix' while calling $className::$name()");
            }

            // const name to label
            $constArr = array_map(function ($value) use($constPrefix) {
                // cut off prefix
                $value = substr($value, strlen($constPrefix));
                // transform to words by separators
                $value = str_replace(['-', '_', '.', ], ' ', $value);
                // transform to words by camelCase
                $value = preg_replace('/(?<![A-Z\s])[A-Z]/', ' \0', $value);
                // ucwords only if all letters are uppercased
                if (preg_match('/^([A-Z\s])*$/', $value)) {
                    $value = ucwords( strtolower($value) );
                }
                return trim($value);
            }, array_flip($constArr));

            // try to get overwritten labels
            // example: static::$constMagicLabels['Status' => [100 => 'Another Active']]
            $customLabelsName = static::$constMagicCustomLabels;
            if (property_exists($className, $customLabelsName) && ($customLabels = static::$$customLabelsName)
                || method_exists($className, $customLabelsName) && ($customLabels = static::$$customLabelsName())
                AND is_array($customLabels) AND isset($customLabels[$methodSuffix])
            ) {
                $constArr = array_replace($constArr, $customLabels[$methodSuffix]);
            }

            // remember array
            if ([] === $arguments) {
                self::$cacheConstMethod[$constPrefix] = $constArr;
            }
        }

        // return labels array
        if ([] === $arguments){
            return $constArr;
        }

        // return selected label by constant value
        $key = array_shift($arguments);
        if (!array_key_exists($key, $constArr)) {
            throw new \InvalidArgumentException("Invalid argument '$key' while calling $className::$name($key)");
        }
        return $constArr[$key];
    }
}
