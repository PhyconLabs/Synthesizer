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
        if ($this->hasInstanceSynthesizedProperty($property)) {
            return $this->getInstanceSynthesizedProperty($property)->invokeGetter();
        } else {
            if ($this->hasParentSynthesizerMethod("__get")) {
                return parent::__get($property);
            } else {
                $class = get_called_class();
                
                throw new Exceptions\BadPropertyException(
                    "Property `{$class}::\${$property}` doesn't exist."
                );
            }
        }
    }
    
    public function __set($property, $value)
    {
        if ($this->hasInstanceSynthesizedProperty($property)) {
            $property = $this->getInstanceSynthesizedProperty($property);
            
            if (!$property->isMutable()) {
                $class = get_class($this);
                
                throw new Exceptions\ImmutablePropertyException(
                    "Property `{$class}::\${$property->getName()}` is immutable."
                );
            }
            
            $property->invokeSetter($value);
        } else {
            if ($this->hasParentSynthesizerMethod("__set")) {
                parent::__set($property, $value);
            } else {
                $class = get_called_class();
                
                throw new Exceptions\BadPropertyException(
                    "Property `{$class}::\${$property}` doesn't exist."
                );
            }
        }
    }
    
    public function __isset($property)
    {
        try {
            return !is_null($this->__get($property));
        } catch (Exceptions\BadPropertyException $e) {
            if ($this->hasParentSynthesizerMethod("__isset")) {
                return parent::__isset($property);
            } else {
                return false;
            }
        }
    }
    
    public function __unset($property)
    {
        if ($this->hasInstanceSynthesizedProperty($property)) {
            $this->__set($property, null);
        } else {
            if ($this->hasParentSynthesizerMethod("__unset")) {
                parent::__unset($property);
            } else {
                $this->__set($property, null);
            }
        }
    }
    
    public function __call($method, array $arguments)
    {
        $type = (strlen($method) > 3) ? substr($method, 0, 3) : false;
        
        if (in_array($type, [ "get", "set" ])) {
            $property = substr($method, 3);
            $property = lcfirst($property);
            
            if ($this->hasInstanceSynthesizedProperty($property)) {
                switch ($type) {
                    case "get":
                        return $this->__get($property);
                    
                    case "set":
                        if (!array_key_exists(0, $arguments)) {
                            $class = get_called_class();
                            
                            throw new BadMethodCallException(
                                "Setter `{$class}::{$method}()` requires one argument that contains value for property."
                            );
                        }
                        
                        $this->__set($property, $arguments[0]);
                        
                        return $this;
                }
            }
        }
        
        if ($this->hasParentSynthesizerMethod("__call")) {
            return parent::__call($method, $arguments);
        } else {
            $class = get_called_class();
            
            throw new Exceptions\BadMethodException(
                "Method `{$class}::{$method}()` doesn't exist."
            );
        }
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
    
    protected function getInstanceSynthesizedProperty($name)
    {
        $properties = $this->getInstanceSynthesizedProperties();
        
        if ($properties->hasProperty($name)) {
            return $properties->getProperty($name);
        } else {
            $class = get_called_class();
            
            throw new Exceptions\BadPropertyException(
                "Property `{$class}::\${$property}` doesn't exist."
            );
        }
    }
    
    protected function hasInstanceSynthesizedProperty($name)
    {
        $properties = $this->getInstanceSynthesizedProperties();
        
        return $properties->hasProperty($name);
    }
    
    protected function hasParentSynthesizerMethod($method)
    {
        $parentClass = get_parent_class();
        
        return ($parentClass !== false && method_exists($parentClass, $method));
    }
}