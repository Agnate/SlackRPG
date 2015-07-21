<?php

class ItemType {
  const POWERSTONE = 'powerstone';
  const ORE = 'ore';
  const ANIMAL = 'animal';
  const HERB = 'herb';
  const REVIVAL = 'revival';
  const KIT = 'kit';

  public static function ALL () {
    return array(
      ItemType::POWERSTONE, ItemType::ORE, ItemType::ANIMAL, ItemType::HERB, ItemType::REVIVAL,
      ItemType::KIT
    );
  }

  public static function PROBABILITIES () {
    $types = array();
    $types[ItemType::ORE] = 0.68;
    $types[ItemType::POWERSTONE] = 0.05;
    $types[ItemType::ANIMAL] = 0.10;
    $types[ItemType::HERB] = 0.15;
    $types[ItemType::REVIVAL] = 0.02;
    $types[ItemType::KIT] = 0;

    return $types;
  }
}