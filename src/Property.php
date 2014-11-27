<?php
namespace SDS\Synthesizer;

use \ReflectionClass;
use \ReflectionProperty;
use \ReflectionException;

class Property
{
    protected $name;
    protected $value;
    protected $defaultValue;
    protected $getter;
    protected $setter;
    protected $isMutable;
    protected $meta;
    protected $instance;
    protected $isDirty;
    
    public static function createFromReflection(
        ReflectionClass $reflectedClass,
        ReflectionProperty $reflectedProperty,
        $options = null
    ) {
        $name = substr($reflectedProperty->getName(), 1);
        
        if (!is_array($options)) {
            $options = [ "defaultValue" => $options ];
        }
        
        static::ensurePropertyMethod($name, $reflectedClass, $options, "getter", "get");
        static::ensurePropertyMethod($name, $reflectedClass, $options, "setter", "set");
        
        return new static($name, $options);
    }
    
    protected static function ensurePropertyMethod(
        $name,
        ReflectionClass $reflectedClass,
        array &$options,
        $optionName,
        $methodPrefix
    ) {
        if (!isset($options[$optionName])) {
            $method = $methodPrefix . ucfirst($name);
            
            try {
                $method = $reflectedClass->getMethod($method);
                
                if ($method->isPublic() && !$method->isStatic()) {
                    $options[$optionName] = $method->getName();
                }
            } catch (ReflectionException $e) {
                // do nothing
            }
        }
    }
    
    public function __construct($name, $options = null)
    {
        $this->meta = [];
        $this->isDirty = false;
        
        $this->setName($name);
        $this->setOptions($options);
    }
    
    public function cloneForInstance($instance)
    {
        $clone = clone $this;
        $clone->setInstance($instance);
        
        return $clone;
    }
    
    public function &invokeGetter()
    {
        $getter = $this->getGetter();
        
        if (isset($getter)) {
            $instance = $this->getInstance();
            
            if (!isset($instance)) {
                throw new Exceptions\NoInstanceException(
                    "Can't invoke getter because property instance isn't set."
                );
            }
            
            return $instance->{$getter}();
        } else {
            $value =& $this->getValue();
            
            if (!isset($value)) {
                $value = $this->getDefaultValue();
            }
        }
        
        return $value;
    }
    
    public function invokeSetter($value)
    {
        $setter = $this->getSetter();
        
        if (isset($setter)) {
            $instance = $this->getInstance();
            
            if (!isset($instance)) {
                throw new Exceptions\NoInstanceException(
                    "Can't invoke setter because property instance isn't set."
                );
            }
            
            $instance->{$setter}($value);
        } else {
            $this->setValue($value);
        }
        
        return $this;
    }
    
    public function getName()
    {
        return $this->name;
    }
    
    public function &getValue()
    {
        return $this->value;
    }
    
    public function setValue($value, $makeDirtyIfChanged = true)
    {
        if ($makeDirtyIfChanged && $this->value !== $value) {
            $this->makeDirty();
        }
        
        $this->value = $value;
        
        return $this;
    }
    
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }
    
    public function getGetter()
    {
        return $this->getter;
    }
    
    public function getSetter()
    {
        return $this->setter;
    }
    
    public function isMutable()
    {
        return $this->isMutable;
    }
    
    public function getMeta($key = null, $default = null)
    {
        if (isset($key)) {
            return array_key_exists($key, $this->meta) ? $this->meta[$key] : $default;
        } else {
            return $this->meta;
        }
    }
    
    public function getInstance()
    {
        return $this->instance;
    }
    
    public function makeDirty()
    {
        $this->isDirty = true;
        
        return $this;
    }
    
    public function makeClean()
    {
        $this->isDirty = false;
        
        return $this;
    }
    
    protected function setName($name)
    {
        $this->name = (string) $name;
        
        return $this;
    }
    
    protected function setOptions($options)
    {
        if (!is_array($options)) {
            $options = [ "defaultValue" => $options ];
        }
        
        if (isset($options["defaultValue"])) {
            $this->setDefaultValue($options["defaultValue"]);
            unset($options["defaultValue"]);
        }
        
        if (isset($options["getter"])) {
            $this->setGetter($options["getter"]);
            unset($options["getter"]);
        }
        
        if (isset($options["setter"])) {
            $this->setSetter($options["setter"]);
            unset($options["setter"]);
        }
        
        if (isset($options["isMutable"])) {
            $this->setIsMutable($options["isMutable"]);
            unset($options["isMutable"]);
        } else {
            $this->setIsMutable(true);
        }
        
        foreach ($options as $metaKey => $metaValue) {
            $this->setMeta($metaKey, $metaValue);
        }
        
        return $this;
    }
    
    protected function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
        
        return $this;
    }
    
    protected function setGetter($getter)
    {
        $this->getter = (string) $getter;
        
        return $this;
    }
    
    protected function setSetter($setter)
    {
        $this->setter = (string) $setter;
        
        return $this;
    }
    
    protected function setIsMutable($isMutable)
    {
        $this->isMutable = (bool) $isMutable;
        
        return $this;
    }
    
    protected function setMeta($key, $value)
    {
        $this->meta[$key] = $value;
        
        return $this;
    }
    
    protected function setInstance($instance)
    {
        if (!is_object($instance)) {
            $type = gettype($instance);
            
            throw new InvalidInstanceException(
                "Property instance must be an object. `{$type}` given."
            );
        }
        
        $this->instance = $instance;
        
        return $this;
    }
}