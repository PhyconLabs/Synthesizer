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
            }
            catch (ReflectionException $e) {}
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
        if (isset($this->getter)) {
            if (!isset($this->instance)) {
                throw new Exceptions\NoInstanceException(
                    "Can't invoke getter because property instance isn't set."
                );
            }
            
            return $this->instance->{$this->getter}($this);
        } else {
            $value = $this->getValue();
            
            if (!isset($value)) {
                $value = $this->getDefaultValue();
            }
        }
        
        return $value;
    }
    
    public function invokeSetter($value)
    {
        if (isset($this->setter)) {
            //
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
    
    public function getMeta($key = null)
    {
        if (isset($key)) {
            //
        } else {
            return $this->meta;
        }
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
        $this->instance = $instance;
        
        return $this;
    }
}