<?php

/* =============================
    __    ____________________
   / /   /  _/ ___/_  __/ ___/
  / /    / / \__ \ / /  \__ \ 
 / /____/ / ___/ // /  ___/ / 
/_____/___//____//_/  /____/  
                              
================================ */

$adjectives = array('Lonely', 'Eerie', 'Ancient', 'Mossy', 'Pitted', 'Mystic', 'Cobwebbed', 'Looming', 'Decaying', 'Forboding', 'Boiling', 'Bubbling', 'Steaming', 'Silent', 'Putrid', 'Sleepy',
  'Unnerving', 'Rotten', 'Sweltering', 'Fuming', 'Fiery', 'Dark', 'Plagued', 'Golden', 'Sweeping', 'Bumbling', 'Shaded', 'Humid', 'Sticky', 'Muggy', 'Stifling',
  'Roaring', 'Windy', 'Mucky', 'Haunted');


$creature_adjectives = array('Dire', 'Giant', 'Great', 'Demonic', 'Fiery', 'Flaming', 'Snow', 'Icy', 'Polar', 'Hellish', 'Grotesque', 'Moist', 'Hulking', 'Radiant', 'Diabolic', 'Swarming', 'Raging', 'Vicious',
  'Man-eating', 'Killer', 'Voracious', 'Armoured', 'Vampiric', 'Ghastly', 'Ethereal', 'Undead', 'Mutant', 'Gruesome', 'Martial', 'Flying', 'Subterranean', 'Neon', 'Abyssal', 'Mythical', 'Legendary', 'Epic',
  'Gargantuan', 'Colossal', 'Explosive', 'Tyrannic', 'Radioactive', 'Poisonous', 'Venomous', 'Deranged', 'Twisted', 'Vile', 'Wretched', 'Decaying', 'Oozing', 'Enormous', 'Punk', 'Glowing', 'Nasty', 'Putrid', 'Ancient',
  'Mystical', 'Plagued', 'Golden');

$creature_names = array('Angel', 'Banshee', 'Basilisk', 'Behemoth', 'Dragon', 'Centaur', 'Cerberus', 'Chimera', 'Cockatrice', 'Cyclops', 'Demon', 'Dragon', 'Echidna', 'Ghost', 'Ghoul', 'Golem', 'Gorgon',
  'Gremlin', 'Griffin', 'Hydra', 'Imp', 'Kraken', 'Leviathan', 'Python', 'Manticore', 'Medusa', 'Merfolk', 'Minotaur', 'Mummy', 'Naga', 'Necromancer', 'Ogre', 'Orc', 'Pegasus', 'Phoenix', 'Satyr', 'Siren',
  'Skeleton', 'Sphinx', 'Succubus', 'Unicorn', 'Vampire', 'Wendigo', 'Werewolf', 'Wight', 'Wraith', 'Wyrm', 'Wyvern', 'Zombie', 'Hellhound', 'Leprechaun', 'Pixie', 'Troll', 'Drow', 'Dryad', 'Fairy',
  'Genie', 'Goblin', 'Hobgoblin', 'Nymph', 'Ratman', 'Faun', 'Troglodyte', 'Firebird', 'Harpy', 'Peryton', 'Ent', 'Fey', 'Spectre', 'Assassin', 'Ninja', 'Bandit', 'Lycan', 'Slime', 'Nightmare',
  'Wolf', 'Ant', 'Crab', 'Scorpion', 'Spider', 'Eagle', 'Salamander', 'Crow', 'Woodpecker', 'Pigeon', 'Bear', 'Fox', 'Antelope', 'Deer', 'Buffalo', 'Sheep', 'Chicken', 'Rabbit', 'Porcupine',
  'Snake', 'Rattlesnake', 'Mosquito', 'Elephant', 'Alligator', 'Lion', 'Zebra', 'Gazelle', 'Whale', 'Platypus', 'Toad', 'Lemur', 'Leopard', 'Lizard', 'Sloth',
  'Caribou', 'Shark', 'Octopus', 'Space Narwhal', 'Alpaca', 'Hippopotamus', 'Rhinoceros', 'Hyena', 'Coyote', 'Mantis', 'Bat', 'Cobra', 'Gorilla', 'Falcon', 'Eel', 'Pug');

$boss_adjectives = array('Giant', 'Great', 'Demonic', 'Fiery', 'Hellish', 'Grotesque', 'Hulking', 'Raging', 'Man-eating', 'Killer', 'Armoured', 'Vampiric', 'Ghastly', 'Ethereal', 'Undead', 'Mutant', 'Gruesome', 'Mythical', 'Legendary', 'Epic', 'Gargantuan', 'Colossal', 'Explosive', 'Tyrannic', 'Radioactive', 'Deranged', 'Twisted', 'Vile', 'Wretched', 'Decaying', 'Oozing', 'Enormous', 'Punk', 'Glowing', 'Nasty', 'Putrid', 'Ancient', 'Mystical', 'Plagued', 'Golden');

$boss_creature_names = array('Angel', 'Banshee', 'Basilisk', 'Behemoth', 'Dragon', 'Cerberus', 'Chimera', 'Cockatrice', 'Cyclops', 'Demon', 'Dragon', 'Ghost', 'Ghoul', 'Golem', 'Gorgon', 'Hydra', 'Kraken', 'Leviathan', 'Manticore', 'Medusa', 'Minotaur', 'Mummy', 'Naga', 'Necromancer', 'Ogre', 'Orc', 'Phoenix', 'Skeleton', 'Sphinx', 'Succubus', 'Vampire', 'Wendigo', 'Werewolf', 'Wight', 'Wraith', 'Wyrm', 'Wyvern', 'Zombie', 'Hellhound','Troll', 'Genie', 'Goblin', 'Hobgoblin', 'Troglodyte', 'Firebird', 'Harpy', 'Peryton', 'Ent', 'Spectre', 'Assassin', 'Ninja', 'Bandit', 'Lycan', 'Slime', 'Nightmare', 'Wolf', 'Crab', 'Scorpion', 'Spider', 'Eagle', 'Bear', 'Rattlesnake', 'Mosquito', 'Elephant', 'Alligator', 'Lion', 'Whale', 'Octopus', 'Hippopotamus', 'Rhinoceros', 'Hyena', 'Mantis', 'Bat', 'Cobra', 'Gorilla');


$creature_dwelling = array('Dungeon', 'Cave', 'Lair', 'Fossils', 'Cavern', 'Hollow', 'Forest', 'Canyon', 'Gulch', 'Mountain', 'Gorge', 'Ravine', 'Ruins', 'Tower', 'Barrow', 'Crater', 'Den', 'Hideout', 'Hole', 'Crevice', 'Chasm', 'Tunnels', 'Pits', 'Crypt', 'Spire');


$landmark_names = array('Lava Lake', 'Magma Pool', 'Hollow', 'Thicket', 'Brier', 'Forest', 'Meadow', 'Field', 'Lowland', 'Grassland', 'River', 'Stream', 'Brook', 'Creek', 'Rill', 'Swamp',
  'Quagmire', 'Mire', 'Fen', 'Bog', 'Marsh', 'Canyon', 'Gulch', 'Valley', 'Mountain', 'Summit', 'Pass', 'Ridge', 'Gorge', 'Rock', 'Point', 'Basin', 'Lake', 'Spring', 'Weald', 'Tor', 'Loch', 'Vale', 'Dell', 'Moor', 'Knoll', 'Grove', 'Hillock', 'Coppice', 'Glade', 'Glen', 'Cleft', 'Crag', 'Mesa', 'Foothills', 'Bluff', 'Shallows', 'Strand', 'Wetland', 'Heath', 'Comet', 'Crater', 'Ravine', 'Cavern', 'Cove', 'Jungle', 'Grotto', 'Fjord', 'Abyss', 'Crevice', 'Chasm', 'Flatland', 'Savanna', 'Desert', 'Wasteland', 'Prairie', 'Steppes', 'Tundra', 'Barrens', 'Expanse', 'Pit', 'Orchard', 'Wilds', 'Lagoon', 'Volcano', 'Oasis', 'Dunes', 'Waterfall', 'Flower Field', 'Taiga');


$structure_names = array('Standing Stone', 'Menhir', 'Monolith', 'Obelisk', 'Mausoleum', 'Sepulcher', 'Tomb', 'Crypt', 'Vault', 'Catacomb', 'Ruins', 'Moai', 'Pillar', 'Pyramid', 'Graveyard', 'Portal', 'Gateway', 'Crystal', 'Mineral', 'Statues', 'Throne', 'Dias', 'Bridge', 'Castle Ruins', 'Shrine', 'Abandoned Mine', 'Tower', 'Fortress Ruins', 'Hollow Tree', 'Cairn', 'Barrow', 'Arch', 'Dolmen', 'Witch Hut', 'Maze', 'Labyrinth', 'Cemetery', 'Necropolis', 'Beanstalk', 'Spire');


$domicile_names_first = array('Aber', 'Ac', 'Ar', 'Ash', 'Ast', 'Auch', 'Bex', 'Brad', 'Bre', 'Cron', 'Crow', 'Can', 'Chu', 'Dun', 'Drum', 'Eccles', 'Ex', 'Fin', 'Ghyll', 'Ho', 'Hal', 'Hil', 'Kin',
  'Knock', 'Lan', 'Lass', 'Liv', 'Moss', 'Nan', 'Pol', 'Red', 'Ren', 'Rass', 'Ro', 'Tarn', 'Tilly', 'Weald', 'Whel', 'Ynys');

$domicile_names_last = array('afon', 'ard', 'ay', 'beck', 'berg', 'bost', 'burn', 'bury', 'by', 'carden', 'caster', 'chester', 'cot', 'cott', 'creag', 'combe', 'dale', 'don', 'dow', 'field', 'ham', 'ing',
  'cheth', 'keth', 'kirk', 'mouth', 'ness', 'ster', 'tun', 'thwaite', 'wych', 'wardine');

$domicile_formats = array('Castle !name', 'Fort !name', 'Town of !name', '!name Village', '!name Outpost', '!name Mine', '!name Ranch', '!name Farm', 'Church of !name', '!name Palace', '!name Fortress', '!name Borough', '!name Stronghold', '!name Keep', '!name Citadel', '!name Sanctuary', '!name Wall', '!name Frontier', '!name Quarters', '!name Estate', '!name Empire', '!name Kingdom', '!name Mansion', '!name Enclave', '!name Bastille', '!name Garrison');


$items = array('rare stone', 'emerald knife');
$plants = array('vanilla', 'saffron', 'cardamom', 'cloves', 'cinnamon', 'turmeric', 'angelpetals');
$gems = array('diamond', 'ruby', 'emerald', 'sapphire', 'opal', 'onyx', 'crystal', 'amethyst', 'alabaster', 'aquamarine', 'alexandrite', 'peridot', 'topaz', 'garnet', 'citrine', 'tanzanite');
$nature_destroyable = array('the ozone layer', 'the planet\'s core', 'everything that is good', 'the forest', 'the desert', 'the ocean', 'an ancient tower');
$shipments = array('supplies', 'herbs', 'spices', 'food and water', 'expensive linens', 'fancy clothes', 'cattle', 'sheep', 'horses', 'goats', 'chickens', 'llamas', 'bison', 'boars');
$escortable = array('a group of adventurers', 'a power wizard', 'a wise sage', 'the Queen', 'the King', 'a merchant', 'a guild leader', 'a noble', 'an ambassador', 'a circus', 'a travelling band', 'some mercenaries');

$handle = array('Kill', 'Capture', 'Dispose of', 'Take care of', 'Eliminate', 'Destroy', 'Murder', 'Relocate', 'Stop', 'Restrain', 'Arrest', 'Hold off');
$prevent = array('Prevent', 'Stop', 'Halt', 'Interrupt', 'Hamper', 'Hinder', 'Impede', 'Thwart');

/* ===================================================================================
    __    ____  _________  ______________  _   __   _   _____    __  ______________
   / /   / __ \/ ____/   |/_  __/  _/ __ \/ | / /  / | / /   |  /  |/  / ____/ ___/
  / /   / / / / /   / /| | / /  / // / / /  |/ /  /  |/ / /| | / /|_/ / __/  \__ \ 
 / /___/ /_/ / /___/ ___ |/ / _/ // /_/ / /|  /  / /|  / ___ |/ /  / / /___ ___/ / 
/_____/\____/\____/_/  |_/_/ /___/\____/_/ |_/  /_/ |_/_/  |_/_/  /_/_____//____/  
                                                                                   
====================================================================================== */

$location_names = array(
  'domicile' => array(
    '!name' => array(
      'join' => '',
      'parts' => array(
        $domicile_names_first,
        $domicile_names_last,
      ),
    ),
    'format' => $domicile_formats,
  ),
  'structure' => array(
    'parts' => array(
      $adjectives,
      $structure_names,
    ),
  ),
  'landmark' => array(
    'parts' => array(
      $adjectives,
      $landmark_names,
    ),
  ),
  'creature' => array(
    'parts' => array(
      $creature_adjectives,
      $creature_names,
      $creature_dwelling,
    ),
  ),
);



/* ==================================================================
   ____  __  ___________________   _   _____    __  ______________
  / __ \/ / / / ____/ ___/_  __/  / | / /   |  /  |/  / ____/ ___/
 / / / / / / / __/  \__ \ / /    /  |/ / /| | / /|_/ / __/  \__ \ 
/ /_/ / /_/ / /___ ___/ // /    / /|  / ___ |/ /  / / /___ ___/ / 
\___\_\____/_____//____//_/    /_/ |_/_/  |_/_/  /_/_____//____/  
                                                                  
===================================================================== */

/**
 * Token options:
 *
 *    !fullname -> The full name of the location.
 *    !creature -> The creature name (only for Creature locations).
 *    !creatureadj -> The adjective describing the creature name (only for Creature locations).
 *    !creaturefull -> The creature name including the adjective (only for Creature locations).
 *    !name -> The name of the domicile (only for Domicile locations).
 *    !dwelling -> The name of the dwelling/domicile (only for Domicile or Creature locations).
 */

$quest_names = array(
  'investigate' => array(
    'format' => array(
      'Journey into the !fullname',
      'Learn about the !fullname',
      'Enter the !fullname',
      'Escort group to the !fullname',
    ),
  ),
  'aid' => array(
    '!shipment' => array('parts' => array($shipments)),
    'format' => array(
      'Help fortify the !fullname',
      'Bring a shipment of !shipment to !fullname',
    ),
  ),
  'fight' => array(
    '!creature' => array('parts' => array($creature_adjectives, $creature_names)),
    'format' => array(
      'Eliminate the !creature',
      'Collect a specimen of !creature for further study',
    ),
  ),
  'boss' => array(
    '!boss' => array('parts' => array($boss_adjectives, $boss_creature_names)),
    '!nature_destroyable' => array('parts' => array($nature_destroyable)),
    '!handle' => array('parts' => array($handle)),
    '!prevent' => array('parts' => array($prevent)),
    'format' => array(
      '!handle the !boss that is terrorizing villagers near !fullname',
      '!prevent the !boss from destroying !nature_destroyable',
    ),
  ),
  'special' => array(
    '!item' => array('parts' => array($items)),
    '!plants' => array('parts' => array($plants)),
    '!escortable' => array('parts' => array($escortable)),
    'format' => array(
      'Retrieve the !item in the !fullname',
      'Gather some !plants at the !fullname',
      'Sneak into the !fullname and steal the !item',
      'Escort !escortable to the !fullname',
      'Begin construction on a !gem mine at the !fullname',
    ),
  ),
);





// ICON OPTIONS
// ===============
// Structures:
//
// graveyard/tomb
// portal/gateway
// crystal/mineral
// obelisk
// standing stones/statues
// throne/dias
// bridge
// castle ruins
// shrine
// pyramid
// mines
// moai

// Landmarks:
//
// lake
// oasis
// giant tree
// beanstalk
// volcano
// mountain
// lava pool
// magma crust
// dunes
// caves
// waterfall
// flower field
// fossils

// Domiciles:
//
// farm house
// mansion
// village
// town
// fort
// castle
// tower
// library/archive
// city-castle

// Other/Special:
//
// floating island