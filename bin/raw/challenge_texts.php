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
    ),
    'crit' => array(
      "!wchamp and !lchamp clash together with the steel of their weapons ringing loudly throughout the arena, but !wchamp overpowers !lchamp.",
    ),
  ),
  'defend' => array(
    'normal' => array(
      "!wchamp raises !wposspronoun shield just in time to deflect !lchamp's strike.",
      "!wchamp blocks !lchamp's thrust with the flat of !wposspronoun blade.",
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