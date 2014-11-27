<?php
namespace SDS\Synthesizer;

use \Closure;
use \Iterator;
use \ReflectionClass;
use \ReflectionProperty;
use \SDS\ClassSupport\Iterators\PropertyArrayIterable;

class PropertyContainer implements Iterator
{
    use PropertyArrayIterable;
    
    protected $properties;
    
    protected $iterableArrayProperty = "properties";
    
    public static function createForClass($class)
    {
        $properties = [];
        $reflectedClass = new ReflectionClass($class);
        $classProperties = $reflectedClass->getProperties();
        $classPropertyDefaults = $reflectedClass->getDefaultProperties();
        
        foreach ($classProperties as $reflectedProperty) {
            $propertyName = $reflectedProperty->getName();
            
            if ($propertyName[0] === "_" && $reflectedProperty->isProtected() && !$reflectedProperty->isStatic()) {
                $propertyOptions = null;
                
                if (isset($classPropertyDefaults[$propertyName])) {
                    $propertyOptions = $classPropertyDefaults[$propertyName];
                }
                
                $properties[] = static::makeProperty($reflectedClass, $reflectedProperty, $propertyOptions);
            }
        }
        
        return new static($properties);
    }
    
    protected static function makeProperty(
        ReflectionClass $reflectedClass,
        ReflectionProperty $reflectedProperty,
        $propertyOptions = null
    ) {
        return Property::createFromReflection($reflectedClass, $reflectedProperty, $propertyOptions);
    }
    
    public function __construct(array $properties)
    {
        $this->properties = [];
        
        foreach ($properties as $property) {
            $this->addProperty($property);
        }
    }
    
    public function cloneForInstance($instance)
    {
        $clones = [];
        
        foreach ($this as $property) {
            $clones[] = $property->cloneForInstance($instance);
        }
        
        return new static($clones);
    }
    
    public function getProperty($name)
    {
        return $this->hasProperty($name) ? $this->properties[$name] : null;
    }
    
    public function hasProperty($name)
    {
        return isset($this->properties[$name]);
    }
    
    protected function addProperty(Property $property)
    {
        $this->properties[$property->getName()] = $property;
        
        return $this;
    }
}