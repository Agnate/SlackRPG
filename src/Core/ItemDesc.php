<?php

class ItemDesc {
  public static function get ($item) {
    switch ($item->name_id) {
      case 'powerstone_shaman': return 'The '.$item->name.' transforms one of your Adventurers into a Shaman. Shamans reduce your death rate on Quests.';
      case 'powerstone_brigand': return 'The '.$item->name.' transforms one of your Adventurers into a Brigand. Brigands increase the amount of gold and items you find.';
      case 'powerstone_judge': return 'The '.$item->name.' transforms one of your Adventurers into a Judge. Judges increase the fame you receive from completing Quests.';
      case 'powerstone_magus': return 'The '.$item->name.' transforms one of your Adventurers into a Magus. Magi increase the experience you receive from completing Quests.';
      case 'powerstone_dragoon': return 'The '.$item->name.' transforms one of your Adventurers into a Dragoon. Dragoons increase your success rate against creature and boss Quests and reduce the time it takes to complete those Quests.';
      case 'powerstone_strider': return 'The '.$item->name.' transforms one of your Adventurers into a Strider. Striders reduce your travel time and can also go exploring on their own.';
      case 'powerstone_oracle': return 'The '.$item->name.' transforms one of your Adventurers into a Oracle. Oracles increase your success rate on Quests.';
      case 'powerstone_juggernaut': return 'The '.$item->name.' transforms one of your Adventurers into a Juggernaut. Juggernauts count as having two additional levels.';

      case 'ore_iron': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_steel': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_mithril': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_crystal': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_diamond': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_adamantine': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_demonite': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';
      case 'ore_godstone': return $item->name.' is used to upgrade your Guild\'s weapons and armour.';

      case 'animal_horse': return $item->name.' is used to upgrade your Guild\'s method of travel.';
      case 'animal_pegasus': return $item->name.' is used to upgrade your Guild\'s method of travel.';

      case 'herb_green': return $item->name.' is used to upgrade your Healer\'s Garden.';
      case 'herb_red': return $item->name.' is used to upgrade your Apothecary.';
    }
    return '';
  }
}