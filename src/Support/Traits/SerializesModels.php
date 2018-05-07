<?php

namespace Lighthouse\Support\Traits;

use ReflectionClass;
use ReflectionProperty;
use Illuminate\Contracts\Database\ModelIdentifier;

trait SerializesModels
{
    use SerializesAndRestoresModelIdentifiers;

    /**
     * Prepare the instance for serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $properties = (new ReflectionClass($this))->getProperties();

        foreach ($properties as $property) {
            $property->setValue($this, $this->getSerializedPropertyValue(
                $this->getPropertyValue($property)
            ));
        }

        return array_values(array_filter(array_map(function ($p) {
            return $p->isStatic() ? null : $p->getName();
        }, $properties)));
    }

    /**
     * Restore the model after serialization.
     *
     * @return void
     */
    public function wakeup()
    {
        foreach ((new ReflectionClass($this))->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

         	$value = $this->getPropertyValue($property);
            if (is_array($value) && array_keys($value) == ["class", "id", "relations", "connection"]){
                $value = new ModelIdentifier($value['class'], $value['id'], $value['relations'], $value['connection']);
            }

            $property->setValue($this, $this->getRestoredPropertyValue(
                $this->getPropertyValue($property)
            ));
        }
    }

    /**
     * Get the property value for the given property.
     *
     * @param  \ReflectionProperty  $property
     * @return mixed
     */
    protected function getPropertyValue(ReflectionProperty $property)
    {
        $property->setAccessible(true);

        return $property->getValue($this);
    }
}
