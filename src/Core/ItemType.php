<?php

class ItemType {
  const POWERSTONE = 'powerstone';
  const ORE = 'ore';

  public static function ALL () {
    return array(ItemType::POWERSTONE, ItemType::ORE);
  }
}