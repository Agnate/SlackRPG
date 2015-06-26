<?php

class ItemType {
  const POWERSTONE = 'powerstone';
  const ORE = 'ore';
  const ANIMAL = 'animal';
  const HERB = 'herb';

  public static function ALL () {
    return array(ItemType::POWERSTONE, ItemType::ORE, ItemType::ANIMAL, ItemType::HERB);
  }

  public static function PROBABILITIES () {
    $types = array();
    $types[ItemType::ORE] = 0.70;
    $types[ItemType::POWERSTONE] = 0.05;
    $types[ItemType::ANIMAL] = 0.10;
    $types[ItemType::HERB] = 0.15;
    return $types;
  }
}