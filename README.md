# Reflection

## Description

Helpers and traits for work with class reflections



## Components

### ReflectionHelper

Helper for work with class reflections. Is a base of other components

##### Usage

1. Get list of class methods

```php
ReflectionHelper::getMethods($className, $filter=null, $prefix=null);
```

2. Get list of class constants

```php
ReflectionHelper::getConstants(string $className, ?string $prefix = ''): array
```

3. Detect type of var (array, int, string etc)

```php
ReflectionHelper::detectVarType($var, $allowTypesArr=[], $default=null)
```

### ConstantTrait

Handle magic calls to get array of class's constant labels or single constant label by it value 

##### Usage

Sourse class

```php
class Simple
{
    use ConstantTrait;
    
    const GROUP_TEST_ONEWORD = 1;
    const GROUP_TEST_TWO_WORDS = 2;
    const GROUP_TEST_COM_plex_LAbeL = 3;
    
    public function test() { return $this->constGroupTest(); }
    public function testStatic() { return static::constGroupTest(); }
    public function testSingleLabel() { return $this->constGroupTest(static::GROUP_TEST_TWO_WORDS); }
    
    public function testInvalidPrefix() { return $this->invalidGroupTest(); }
    public function testInvalidConstGroup() { return $this->constInvalidGroup(); }
    public function testInvalidConstValue() { return $this->constGroupTest(1000); }
}
```

Anywhere usage

```php
$simple = new Simple;
 
//
// will return CORRECT results
//
$simple->test();
// or
$simple->testStatic();
// or
Simple::testStatic();
// returns array(3) { [1]=> string(7) "Oneword" [2]=> string(10) "Two Words" [3]=> string(16) "COM plex LAbe L" }
 
$simple->testSingleLabel();
// returns string(10) "Two Words"
 
//
// will throw an ERRORS
//
$simple->testInvalidPrefix();
// or
$simple->testInvalidConstGroup();
// if no parent::__call and/or parent::__callStatic exists then
//      throws Error "Call to undefined method Simple::invalidGroupTest()" or "testInvalidConstGroup"
// else
//      throws error from parent
 
$simple->testInvalidConstValue();
// throws Error "Invalid argument '1000' while calling Simple::constGroupTest(1000)"
```

So correct results (not for single call) will be like this:

```php
[
    1 => "Oneword",
    2 => "Two Words",
    3 => "COM plex LAbe L",
]
```

For single label call - `Oneword`
 
##### Customization

***Change prefix for the const getter***

Define somewhere before call const getter:
```php
static::$constMagicGetterPrefix = 'get';
...
Simple::getGroupTest();
```

**Note**: attempt to override static property like below will trigger an error

```php
protected static $constMagicGetterPrefix = 'get';
```

***Add/override constant label for get complex label formatting***

Sourse class

```php
class Simple
{
    ...
    protected static $constMagicLabels = ['GroupTest' => [3 => 'COMP-lex-LaBEL', 0 => 'Extra label']];
    ...
// or
    ...
    protected static constMagicLabels() { return ['GroupTest' => [3 => 'COMP-lex-LaBEL', 0 => 'Extra label']]; }
    ...
}
```

Anywhere usage

```php
$simple->test();
// returns array(4) { [1]=> string(7) "Oneword" [2]=> string(10) "Two Words" [3]=> string(14) "COMP-lex-LaBEL" [0]=> string(11) "Extra label" }
```

So correct results in such case will be like this:

```php
[
    1 => "Oneword",
    2 => "Two Words",
    3 => "COMP-lex-LaBEL",  // was replaced
    0 => "Extra label",     // was added
]
```

**Note**: you could change name of property/method of label customization via set before calling getter

```php
static::$constMagicCustomLabels = 'renamedConstMagicLabels';
```
