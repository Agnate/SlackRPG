<?php

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


$adjectives = array('Lonely', 'Eerie', 'Ancient', 'Mossy', 'Pitted', 'Mystic', 'Cobwebbed', 'Looming', 'Decaying', 'Forboding', 'Boiling', 'Bubbling', 'Steaming', 'Silent', 'Putrid', 'Sleepy',
  'Unnerving', 'Rotten', 'Sweltering', 'Fuming', 'Fiery', 'Dark', 'Plagued', 'Golden', 'Sweeping', 'Bumbling', 'Shaded', 'Humid', 'Sticky', 'Muggy', 'Stifling',
  'Roaring', 'Windy', 'Mucky');


$creature_adjectives = array('Dire', 'Giant', 'Great', 'Demonic', 'Fiery', 'Flaming', 'Snow', 'Icy', 'Polar', 'Hellish', 'Grotesque', 'Moist', 'Hulking', 'Radiant', 'Diabolic', 'Swarming', 'Raging', 'Vicious',
  'Man-eating', 'Killer', 'Voracious', 'Armoured', 'Vampiric', 'Ghastly', 'Ethereal', 'Haunted', 'Undead', 'Mutant', 'Gruesome', 'Martial', 'Flying', 'Subterranean', 'Neon', 'Abyssal', 'Mythic', 'Legendary', 'Epic',
  'Gargantuan', 'Colossal', 'Explosive', 'Tyrannic', 'Radioactive', 'Poisonous', 'Venomous', 'Deranged', 'Twisted', 'Vile', 'Wretched', 'Decaying', 'Oozing', 'Enormous', 'Punk', 'Glowing', 'Nasty', 'Putrid', 'Ancient',
  'Mystical', 'Plagued', 'Golden');

$creature_names = array('Angel', 'Banshee', 'Basilisk', 'Behemoth', 'Dragon', 'Centaur', 'Cerberus', 'Chimera', 'Cockatrice', 'Cyclops', 'Demon', 'Dragon', 'Echidna', 'Ghost', 'Ghoul', 'Golem', 'Gorgon',
  'Gremlin', 'Griffin', 'Hydra', 'Imp', 'Kraken', 'Leviathan', 'Python', 'Manticore', 'Medusa', 'Merfolk', 'Minotaur', 'Mummy', 'Naga', 'Necromancer', 'Ogre', 'Orc', 'Pegasus', 'Phoenix', 'Satyr', 'Siren',
  'Skeleton', 'Sphinx', 'Succubus', 'Unicorn', 'Vampire', 'Wendigo', 'Werewolf', 'Wight', 'Wraith', 'Wyrm', 'Wyvern', 'Zombie', 'Hellhound', 'Leprechaun', 'Pixie', 'Troll', 'Drow', 'Dryad', 'Fairy',
  'Genie', 'Goblin', 'Hobgoblin', 'Nymph', 'Ratman', 'Faun', 'Troglodyte', 'Firebird', 'Harpy', 'Peryton', 'Ent', 'Fey', 'Spectre', 'Assassin', 'Ninja', 'Bandit', 'Tauren', 'Lycan', 'Slime', 'Nightmare',
  'Wolf', 'Ant', 'Crab', 'Lobster', 'Scorpion', 'Spider', 'Eagle', 'Salamander', 'Crow', 'Woodpecker', 'Pigeon', 'Bear', 'Fox', 'Antelope', 'Deer', 'Buffalo', 'Sheep', 'Chicken', 'Rabbit', 'Porcupine',
  'Snake', 'Rattlesnake', 'Mosquito', 'Elephant', 'Alligator', 'Lion', 'Zebra', 'Gazelle', 'Whale', 'Platypus', 'Toad', 'Lemur', 'Leopard', 'Lizard', 'Sloth', 'Giraffe', 'Moose', 'Reindeer',
  'Caribou', 'Koala', 'Penguin', 'Shark', 'Octopus', 'Narwhal', 'Cow', 'Rooster', 'Llama', 'Emu', 'Alpaca', 'Hippopotamus', 'Rhino', 'Hyena', 'Coyote', 'Mole', 'Mantis', 'Dragonfly', 'Bat', 'Cobra',
  'Monkey', 'Horse', 'Boar', 'Fly', 'Falcon', 'Eel', 'Pug');


$creature_dwelling = array('Dungeon', 'Cave', 'Lair', 'Fossils', 'Cavern', 'Hollow', 'Forest', 'Canyon', 'Gulch', 'Mountain', 'Gorge', 'Ravine', 'Ruins', 'Tower', 'Barrow', 'Crater', 'Den', 'Hideout', 'Hole', 'Crevice', 'Chasm', 'Tunnels', 'Pits', 'Crypt', 'Spire');


$landmark_names = array('Lava Lake', 'Magma Pool', 'Hollow', 'Thicket', 'Brier', 'Forest', 'Meadow', 'Field', 'Lowland', 'Grassland', 'River', 'Stream', 'Brook', 'Creek', 'Rill', 'Swamp',
  'Quagmire', 'Mire', 'Fen', 'Bog', 'Marsh', 'Canyon', 'Gulch', 'Valley', 'Mountain', 'Summit', 'Pass', 'Ridge', 'Gorge', 'Rock', 'Point', 'Basin', 'Lake', 'Spring', 'Weald', 'Tor', 'Loch', 'Vale', 'Dell', 'Moor', 'Knoll', 'Grove', 'Hillock', 'Coppice', 'Glade', 'Glen', 'Cleft', 'Crag', 'Mesa', 'Foothills', 'Bluff', 'Shallows', 'Strand', 'Wetland', 'Heath', 'Comet', 'Crater', 'Ravine', 'Cavern', 'Cove', 'Jungle', 'Grotto', 'Fjord', 'Abyss', 'Crevice', 'Chasm', 'Flatland', 'Savanna', 'Desert', 'Wasteland', 'Prairie', 'Steppes', 'Tundra', 'Barrens', 'Expanse', 'Pit', 'Orchard', 'Wilds', 'Lagoon', 'Volcano', 'Oasis', 'Dunes', 'Waterfall', 'Flower Field', 'Taiga');


$structure_names = array('Standing Stone', 'Menhir', 'Monolith', 'Obelisk', 'Mausoleum', 'Sepulcher', 'Tomb', 'Crypt', 'Vault', 'Catacomb', 'Ruins', 'Moai', 'Pillar', 'Pyramid', 'Graveyard', 'Portal', 'Gateway', 'Crystal', 'Mineral', 'Statues', 'Throne', 'Dias', 'Bridge', 'Castle Ruins', 'Shrine', 'Abandoned Mine', 'Tower', 'Fortress Ruins', 'Hollow Tree', 'Cairn', 'Barrow', 'Arch', 'Dolmen', 'Witch Hut', 'Maze', 'Labyrinth', 'Cemetery', 'Necropolis', 'Beanstalk', 'Spire');


$domicile_names_first = array('Aber', 'Ac', 'Ar', 'Ash', 'Ast', 'Auch', 'Bex', 'Brad', 'Bre', 'Cron', 'Crow', 'Can', 'Chu', 'Dun', 'Drum', 'Eccles', 'Ex', 'Fin', 'Ghyll', 'Ho', 'Hal', 'Hil', 'Kin',
  'Knock', 'Lan', 'Lass', 'Liv', 'Moss', 'Nan', 'Pol', 'Red', 'Ren', 'Rass', 'Ro', 'Tarn', 'Tilly', 'Weald', 'Whel', 'Ynys');

$domicile_names_last = array('afon', 'ard', 'ay', 'beck', 'berg', 'bost', 'burn', 'bury', 'by', 'carden', 'caster', 'chester', 'cot', 'cott', 'creag', 'combe', 'dale', 'don', 'dow', 'field', 'ham', 'ing',
  'cheth', 'keth', 'kirk', 'mouth', 'ness', 'ster', 'tun', 'thwaite', 'wych', 'wardine');

$domicile_formats = array('Castle !name', 'Fort !name', 'Town of !name', '!name Village', '!name Outpost', '!name Mine', '!name Ranch', '!name Farm', 'Church of !name', '!name Palace', '!name Fortress', '!name Borough', '!name Stronghold', '!name Keep', '!name Citadel', '!name Sanctuary', '!name Wall', '!name Frontier', '!name Quarters', '!name Estate', '!name Empire', '!name Kingdom', '!name Mansion', '!name Enclave', '!name Bastille', '!name Garrison');



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