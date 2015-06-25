<?php

class ItemType {
  const POWERSTONE = 'powerstone';
  const ORE = 'ore';

  public static function ALL () {
    return array(ItemType::POWERSTONE, ItemType::ORE);
  }

  public static function PROBABILITIES () {
    $types = array();
    $types[ItemType::ORE] = 0.95;
    $types[ItemType::POWERSTONE] = 0.05;
    return $types;
  }
}