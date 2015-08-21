<?php

/**
 * Replacement patterns:
 * 
 * !wchamp -> The winning Champion's name.
 * !wchamp's -> The winning Champion's name with the possessive.
 * !wgender -> The winning Champion's gender (male/female).
 * !wpronoun -> The winning Champion's pronoun (he/she).
 * !wposspronoun -> The winning Champion's possessive pronoun (his/her).
 * !wotherpronoun -> The winning Champion's possessive pronoun (him/her).
 * !lchamp -> The losing Champion's name.
 * !lchamp's -> The losing Champion's name with the possessive.
 * !lgender -> The losing Champion's gender (male/female).
 * !lpronoun -> The losing Champion's pronoun (he/she).
 * !lposspronoun -> The losing Champion's possessive pronoun (his/her).
 * !lotherpronoun -> The losing Champion's possessive pronoun (him/her).
 *
 */

$challenge_texts = array();

$challenge_texts['attack'] = array(
  'attack' => array(
    'normal' => array(
      "!wchamp and !lchamp clash together with the steel of their weapons ringing loudly throughout the arena.",
      "Both champions lunge at each other but both attacked empty air.",
      "!wchamp flings a throwing dagger at !lchamp, but !lchamp brings !lposspronoun blade up while attempting to strike to deflect the dagger instead.",
      "Both champions throw shurikens at each other, but the shurikens collide and clatter to the ground.",
    ),
    'crit' => array(
      "!wchamp and !lchamp clash together with the steel of their weapons ringing loudly throughout the arena, but !wchamp overpowers !lchamp.",
      "Both champions lunge at each other. !lchamp attacks empty air as !wchamp slides !wposspronoun rapier through the opening that !lchamp left.",
      "!wchamp flings a throwing dagger at !lchamp, but !lchamp can't bring !lposspronoun blade up fast enough to deflect the dagger.",
      "Both champions throw shurikens at each other. !lchamp's shuriken almost knocks !wchamp's shuriken off course, but it still lodges itself in !lchamp's arm.",
    ),
  ),
  'defend' => array(
    'normal' => array(
      "!wchamp raises !wposspronoun shield just in time to deflect !lchamp's strike.",
      "!wchamp blocks !lchamp's thrust with the flat of !wposspronoun blade.",
      "!lchamp swings !lposspronoun greataxe at !wchamp. !wchamp plants !wposspronoun halberd into the ground to brace against the mighty swing, grinding the greataxe to a halt.",
      "!wchamp twirls !wposspronoun quarterstaff just in time to knock away the series of darts flying from !lchamp's position.",
    ),
    'miss' => array(
      "!wchamp raises !wposspronoun shield but !lchamp's strike slips past !wposspronoun defenses.",
    ),
    'crit' => array(
      "!wchamp raises !wposspronoun shield deflecting !lchamp's strike and causing !lotherpronoun to stagger backward.",
    ),
  ),
  'break' => array(
    'normal' => array(
      "!lchamp tries to break through !wchamp's defense but succumbs to a counter-attack instead.",
    ),
    'miss' => array(
      "!lchamp tries to break through !wchamp's defense but !wchamp misses on the counter-attack.",
    ),
    'crit' => array(
      "!lchamp tries to break through !wchamp's defense but trips and leaves !lotherpronounself open to a devastating attack.",
    ),
  ),
);

$challenge_texts['defend'] = array(
  'attack' => $challenge_texts['attack']['defend'],
  'defend' => array(
    'normal' => array(
      "!wchamp and !lchamp both anticipate an attack from the other and choose to bolster their defenses.",
    ),
    'crit' => array(
      "!wchamp and !lchamp both anticipate an attack from the other and choose to bolster their defenses, but !wchamp managed to trip !lchamp.",
    ),
  ),
  'break' => array(
    'normal' => array(
      "!wchamp feints a thrust to provoke a defensive stance from !lchamp and uses that opportunity to body-check !lchamp.",
    ),
    'miss' => array(
      "!wchamp feints a thrust to provoke a defensive stance from !lchamp but !lpronoun doesn't fall for it.",
    ),
    'crit' => array(
      "!wchamp feints a thrust to provoke a defensive stance from !lchamp and uses that opportunity to bash !lchamp hard with !wposspronoun shield.",
    ),
  ),
);

$challenge_texts['break'] = array(
  'attack' => $challenge_texts['attack']['break'],
  'defend' => $challenge_texts['defend']['break'],
  'break' => array(
    'normal' => array(
      "Both champions rush into each other trying to throw the other off balance.",
    ),
    'crit' => array(
      "Both champions rush into each other trying to throw the other off balance and !wchamp succeeds.",
    ),
  ),
);


/*
IDEAS:

$PC stabs at $foe, but $foe parries.
Dude cites the 21 shinigami prayers consisting of 105 different lines each to power up his righteous slash of righteousness but Dude2 blocks
but $foe ducks out of the way
but it is only glances off $foe’s armor
Dude1 screams so loud that Dude2's head implodes.
bring down $his axe
Swings $his axe
slashes with $his axe
Shoryuken! But $foe blocks at the other edge of the map.
cleaves with $his axe
DudeA calms his/her chaotic thoughts and unleashes a torrent of evil spirits. DudeB dodges
DudeA steadies his/her aim and unleashes a bolt. DudeB ducks
DudeA steadies his/her aim and unleashes a bolt. DudeB catches it and throws it back! DudeA ded
DudeA seeks deep within him/herself and finds the ancient wisdom handed down from his/her ancestors... offers to discuss differences in a civilized manner. DudeB chops DudeA's head off
DudeA points his crossbow at his/her own head and fires! This summons a Persona that casts Zio towards DudeB
DudeA steadies his/her aim and unleashes a bolt. DudeB tries to catch it, fumbles, and stands there looking awkward and mildly injured.
DudeB tries to catch it. The arrow buries itself in DudeB’s hand. Good catch!
DudeA mutters under his/her breath. DudeB begins to feel faint and raises his/her blade to his/her own neck and slashes!
DudeA motocop photoshops at DudeB who eats him/her like a damn panini.
DudeA throws a fireball. DubeB’s eyebrows are lightly singed as DudeB dodges.
DudeA throws a fireball. DudeB’s armour begins to smoke. 
DudeA throws a fireball. DudeB is gravely injured.
DudeA thrusts his pelvis suggestively. DudeB becomes confused
DudeA slashes. Tyler, far away in the land of Canada, is injured for no comprehensible reason.
DudeA SHWINGS. DudeB feels violated
DudeA shanks DudeB. DudeB is regretting his life of crime.
DudeB had 3 days left on their sentence. DudeB's old lady hires a hit on DudeA. On his/her deathbed, DudeA reveals that DudeB is still alive after all.
DudeA reaches $his hand out into the air, as though to choke DudeB. DudeB is confused! DudeB sticks its head in a turkey/a barrel/a bucket/a latrine.
DudeA eats a creamy tomato soup. He turns around, drops trow, and erupts an effluvious acid that covers DudeB. DudeB counterattacks with vomit!
DudeA yells "CATCH!" and throws his hammer towards DudeB. DudeB tries to catch but is unaware that the hammer is actually Mjolnir. DudeB is not worthy and is crushed instantly.
DudeA shouts his move name "DIE ONE THOUSAND DEATHS". Unfortunately for him, DudeB is master of one thousand deaths and counters with a perfect defence.
DudeA pulls out his gat and goes BLAT BLAT BLAT. DudeB gets wounded, but starts a successful rap career after attaining street cred.

*/