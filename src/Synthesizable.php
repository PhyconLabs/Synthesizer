<?php
namespace SDS\Synthesizer;

trait Synthesizable
{
    protected $instanceSynthesizedProperties;
    
    public static function getSynthesizedProperties()
    {
        static $properties = [];
        
        $calledClass = get_called_class();
        
        if (!isset($properties[$calledClass])) {
            $properties[$calledClass] = static::makeSynthesizedPropertyContainer($calledClass);
        }
        
        return $properties[$calledClass];
    }
    
    public static function getSynthesizedProperty($name)
    {
        return static::getSynthesizedProperties()->getProperty($name);
    }
    
    protected static function makeSynthesizedPropertyContainer($class)
    {
        return PropertyContainer::createForClass($class);
    }
    
    public function &__get($property)
    {
        $properties = $this->getInstanceSynthesizedProperties();
        
        if (!$properties->hasProperty($property)) {
            $class = get_called_class();
            
            throw new Exceptions\BadPropertyException(
                "Property `{$class}::\${$property}` doesn't exist."
            );
        }
        
        return $properties->getProperty($property)->invokeGetter();
    }
    
    public function __set($property, $value)
    {
        $properties = $this->getInstanceSynthesizedProperties();
        
        if (!$properties->hasProperty($property)) {
            $class = get_called_class();
            
            throw new Exceptions\BadPropertyException(
                "Property `{$class}::\${$property}` doesn't exist."
            );
        }
        
        $property = $properties->getProperty($property);
        
        if (!$property->isMutable()) {
            $class = get_class($this);
            
            throw new Exceptions\ImmutablePropertyException(
                "Property `{$class}::\${$property->getName()}` is immutable."
            );
        }
        
        $property->invokeSetter($value);
    }
    
    public function __isset($property)
    {
        try {
            return !is_null($this->__get($property));
        }
        catch (Exceptions\BadPropertyException $e) {
            return false;
        }
    }
    
    public function __unset($property)
    {
        $this->__set($property, null);
    }
    
    protected function initializeSynthesizedProperties()
    {
        $this->instanceSynthesizedProperties = static::getSynthesizedProperties()->cloneForInstance($this);
        
        return $this;
    }
    
    protected function getInstanceSynthesizedProperties()
    {
        if (!isset($this->instanceSynthesizedProperties)) {
            $this->initializeSynthesizedProperties();
        }
        
        return $this->instanceSynthesizedProperties;
    }
}