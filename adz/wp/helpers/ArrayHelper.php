<?php 
namespace AdzHive\helpers;

class ArrayHelper extends \AdzHive\Adz {

function isAssociative($array) {
  return (bool)count(array_filter(array_keys($array), 'is_string'));
}

function keysEqual($a, $b) {
  if (!is_array($a) || !is_array($b))
      return false;

  $keysA = array_keys($a);
  $keysB = array_keys($b);

  return array_diff($keysA, $keysB) === array_diff($keysB, $keysA);
}

function isSchemaEqual($a, $b) {
  if (!$this->keysEqual($a, $b))
      return false;

  foreach ($a as $key => $value) {
      if (is_array($value) && $this->isAssociative($value) && !$this->isSchemaEqual($value, $b[$key]))
          return false;
  }
  return true;
}

}