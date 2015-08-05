<?php

class Challenge extends RPGEntitySaveable {
  // Fields
  public $chid;
  public $challenger_id; // Guild ID
  public $challenger_champ; // Adventurer ID
  public $challenger_moves;
  public $opponent_id; // Guild ID
  public $opponent_champ; // Adventurer ID
  public $opponent_moves;
  public $created;
  public $wager;
  public $confirmed;
  public $winner; // Guild ID
  public $reward;

  // Protected
  protected $_challenger;
  protected $_opponent;

  // Private vars
  static $fields_int = array('created', 'wager', 'reward');
  static $db_table = 'challenges';
  static $default_class = 'Challenge';
  static $primary_key = 'chid';

  const FILENAME_TEXTS_ORIGINAL = '/bin/json/original/challenge_texts.json';
  const FILENAME_TEXTS = '/bin/json/challenge_texts.json';

  const MOVE_ATTACK = 'attack';
  const MOVE_DEFEND = 'defend';
  const MOVE_BREAK = 'break';

  const STATUS_CHALLENGER_WON = 'challenger';
  const STATUS_OPPONENT_WON = 'opponent';
  const STATUS_TIE = 'tie';

  const RESULT_NORMAL = 'normal';
  const RESULT_MISS = 'miss';
  const RESULT_CRIT = 'crit';

  
  function __construct($data = array()) {
    // Perform regular constructor.
    parent::__construct( $data );

    // Set defaults.
    if (empty($this->created)) $this->created = time();
    if (empty($this->confirmed)) $this->confirmed = false;
  }

  public function load_challenger () {
    $this->_challenger = Guild::load(array('gid' => $this->challenger_id));
  }
  public function get_challenger () {
    if (empty($this->_challenger) && !empty($this->challenger_id)) $this->load_challenger();
    return $this->_challenger;
  }
  public function get_challenger_moves () {
    return Challenge::convert_moves_to_list($this->challenger_moves);
  }
  public function set_challenger_moves ($list) {
    $this->challenger_moves = Challenge::convert_list_to_moves($list);
  }

  public function load_opponent () {
    $this->_opponent = Guild::load(array('gid' => $this->opponent_id));
  }
  public function get_opponent () {
    if (empty($this->_opponent) && !empty($this->opponent_id)) $this->load_opponent();
    return $this->_opponent;
  }
  public function get_opponent_moves () {
    return Challenge::convert_moves_to_list($this->opponent_moves);
  }
  public function set_opponent_moves ($list) {
    $this->opponent_moves = Challenge::convert_list_to_moves($list);
  }

  public function queue_process ($queue = null) {
    // If we were give a Queue, destroy it.
    if (!empty($queue)) $queue->delete();

    // Load JSON texts.
    $json = Challenge::load_texts_list();
    $orig_json = Challenge::load_texts_list(true);

    // Get the Guilds.
    $challenger = $this->get_challenger();
    $opponent = $this->get_opponent();

    // Get the champions.
    $cchamp = $challenger->get_champion();
    $ochamp = $opponent->get_champion();

    // Get moves.
    $cmoves = $this->get_challenger_moves();
    $omoves = $this->get_opponent_moves();

    // Challenge info.
    $message = array();
    $info = array(
      'challenger' => $challenger,
      'challenger_champ' => $cchamp,
      'challenger_moves' => $cmoves,
      'challenger_points' => 0,
      'opponent' => $opponent,
      'opponent_champ' => $ochamp,
      'opponent_moves' => $omoves,
      'opponent_points' => 0,
      'num_rounds' => 5,
      'rounds' => 0,
    );

    // $message[] = $challenger->get_display_name() .' challenges '. $opponent->get_display_name() .' to a fight in the Colosseum.';
    $message[] = '';
    $message[] = $challenger->get_display_name() .' has chosen their Champion: '. $cchamp->get_display_name();
    $message[] = $opponent->get_display_name() .' has chosen their Champion: '. $ochamp->get_display_name();
    $message[] = '';
    $message[] = 'Let the fight begin!';
    $message[] = '';

    // Perform first rounds.
    for ($i = 0; $i < $info['num_rounds']; $i++) {
      $info['rounds']++;
      // Get moves.
      $cmove = $cmoves[$i];
      $omove = $omoves[$i];
      // Compare moves.
      $message[] = $this->__compare_moves($info, $json, $orig_json, $cmove, $omove);
    }

    // Check for challenger's Brigand ability to turn a 1-point loss into a tie.
    if ($info['opponent_points'] - $info['challenger_points'] == 1) {
      $loss_by_one_as_tie = $cchamp->get_bonus()->get_mod(Bonus::LOSS_BY_ONE_AS_TIE, Bonus::FOR_DEFAULT, Bonus::MOD_HUNDREDS);
      if ($loss_by_one_as_tie > 0 && rand(1, 100) <= $loss_by_one_as_tie) {
        $info['challenger_points']++;
        $message[] = '*'. $cchamp->get_display_name(false, false, true, false, false) .'* cheats and convinces the referee that it was a tie.';
      }
    }

    // Check for opponent's Brigand ability to turn a 1-point loss into a tie.
    if ($info['challenger_points'] - $info['opponent_points'] == 1) {
      $loss_by_one_as_tie = $ochamp->get_bonus()->get_mod(Bonus::LOSS_BY_ONE_AS_TIE, Bonus::FOR_DEFAULT, Bonus::MOD_HUNDREDS);
      if ($loss_by_one_as_tie > 0 && rand(1, 100) <= $loss_by_one_as_tie) {
        $info['opponent_points']++;
        $message[] = '*'. $ochamp->get_display_name(false, false, true, false, false) .'* cheats and convinces the referee that it was a tie.';
      }
    }

    // If there is a tie, perform a tie-breaker round.
    if ($info['challenger_points'] == $info['opponent_points']) {
      $message[] = 'The fighters are tied with '.$info['opponent_points'].' point'.($info['opponent_points'] != 1 ? 's' : '').' after '.$info['rounds'].' rounds. Time to move onto the tie-breaker round.';
      $info['rounds']++;
      // Get moves.
      $cmove = $cmoves[$info['num_rounds']];
      $omove = $omoves[$info['num_rounds']];
      // Compare moves.
      $message[] = $this->__compare_moves($info, $json, $orig_json, $cmove, $omove);
    }

    // If there is STILL a tie, we randomly choose moves until someone wins.
    $all_moves = Challenge::moves();
    while ($info['challenger_points'] == $info['opponent_points']) {
      $info['rounds']++;
      // Get random moves.
      $cmove = $all_moves[array_rand($all_moves)];
      $omove = $all_moves[array_rand($all_moves)];
      // Compare moves.
      $message[] = $this->__compare_moves($info, $json, $orig_json, $cmove, $omove);
    }

    // Show the challenge results.
    if ($info['challenger_points'] > $info['opponent_points']) {
      $winner = $challenger;
      $loser = $opponent;
    }
    else {
      $winner = $opponent;
      $loser = $challenger;
    }

    $message[] = '';
    $message[] = 'And the winner is: '.$winner->get_display_name().'!';
    $message[] = 'They receive '.Display::get_fame($this->reward).'.';

    // Save the winner information.
    $this->winner = $winner->gid;
    $this->save();

    // Award the fame.
    $winner->fame += $this->reward;
    $winner->save();

    // Remove the adventuring groups and free up the champions.
    $cgroup = AdventuringGroup::load(array('agid' => $cchamp->agid, 'gid' => $challenger->gid, 'task_id' => $this->chid));
    if (!empty($cgroup)) $cgroup->delete();
    $cchamp->agid = 0;
    $cchamp->save();

    $ogroup = AdventuringGroup::load(array('agid' => $ochamp->agid, 'gid' => $opponent->gid, 'task_id' => $this->chid));
    if (!empty($ogroup)) $ogroup->delete();
    $ochamp->agid = 0;
    $ochamp->save();

    // Create results attachment.
    $attachment = new SlackAttachment ();
    $attachment->text = implode("\n", $message);
    $attachment->color = SlackAttachment::COLOR_BLUE;
    $attachment->fallback = $winner->get_display_name().' wins the Colosseum fight against '.$loser->get_display_name().' and receives '.Display::get_fame($this->reward).'.';
    $attachment->title = $challenger->get_display_name(false, false).' vs '.$opponent->get_display_name(false, false);

    // $field = new SlackAttachmentField ();
    // $field->title = 'Fame';
    // $field->value = Display::get_fame($this->reward);
    // $field->short = 'true';
    // $attachment->add_field($field);

    $message = new SlackMessage ();
    $message->text = 'Colosseum fight between '.$challenger->get_display_name() .' and '. $opponent->get_display_name() .' on '. date('M j, Y \a\t H:i:s') .'.';
    $message->add_attachment($attachment);

    // Delete this challenge.
    $this->delete();

    return array('messages' => array($message));
  }

  protected function __compare_moves (&$info, &$json, &$orig_json, $cmove, $omove) {
    $message = array();
    $round = 'Round '.$info['rounds'].': ';

    // Get the move status.
    $oracled = false;
    $status = $this->__get_move_status($cmove, $omove);

    // Check if they can use Oracle move to use their tie-breaker move instead of the move they lost with.
    if ($status != Challenge::STATUS_TIE) {
      // Alter the move and get the new status.
      if ($status == Challenge::STATUS_CHALLENGER_WON && $this->__check_tie_breaker_on_fail($omove, $info['opponent_champ'])) {
        $tie_breaker_move = $info['opponent_moves'][count($info['opponent_moves']) - 1];
        $original_move = $omove;
        $omove = $tie_breaker_move;
        $oracled = true;
        $oracle = $info['opponent_champ'];
        $other = $info['challenger_champ'];
      }
      else if ($status == Challenge::STATUS_OPPONENT_WON && $this->__check_tie_breaker_on_fail($cmove, $info['challenger_champ'])) {
        $tie_breaker_move = $info['challenger_moves'][count($info['challenger_moves']) - 1];
        $original_move = $cmove;
        $cmove = $tie_breaker_move;
        $oracled = true;
        $oracle = $info['challenger_champ'];
        $other = $info['opponent_champ'];
      }

      // If a move was oracled, adjust it now.
      if ($oracled) {
        // If the move is the same, it's the equivalent of the Oracle move "failing".
        if ($original_move == $tie_breaker_move) {
          $message[] = '*'.$oracle->get_display_name(false, false, true, false, false).'* attempted to foresee *'.$other->get_display_name(false, false, true, false, false).$other->get_possessive().'* move but failed.';
        }
        else {
          $status = $this->__get_move_status($cmove, $omove);
          $message[] = '*'.$oracle->get_display_name(false, false, true, false, false).'* foresaw *'.$other->get_display_name(false, false, true, false, false).$other->get_possessive().'* move and switched '.$oracle->get_possessive_pronoun().' move to '.$tie_breaker_move.'.';
        }
      }
    }

    if ($status == Challenge::STATUS_TIE) {
      // Check if we should be altering the tie.
      $challenger_tie_break = false;
      if (rand(1, 100) <= $info['challenger_champ']->get_bonus()->get_mod($this->__get_as_success_bonus($cmove), 'Challenge->'.$cmove, Bonus::MOD_HUNDREDS)) {
        $challenger_tie_break = true;
        $info['challenger_points']++;
      }

      $opponent_tie_break = false;
      if (rand(1, 100) <= $info['opponent_champ']->get_bonus()->get_mod($this->__get_as_success_bonus($omove), 'Challenge->'.$omove, Bonus::MOD_HUNDREDS)) {
        $opponent_tie_break = true;
        $info['opponent_points']++;
      }

      if ($challenger_tie_break && !$opponent_tie_break) {
        $message[] = $round. $this->__get_move_text($info, $json, $orig_json, $cmove, $omove, Challenge::RESULT_CRIT, $info['challenger_champ'], $info['opponent_champ']);
      }
      else if ($opponent_tie_break && !$challenger_tie_break) {
        $message[] = $round. $this->__get_move_text($info, $json, $orig_json, $omove, $cmove, Challenge::RESULT_CRIT, $info['opponent_champ'], $info['challenger_champ']);
      }
      else {
        $message[] = $round. $this->__get_move_text($info, $json, $orig_json, $cmove, $omove, Challenge::RESULT_NORMAL, $info['challenger_champ'], $info['opponent_champ']);
      }
    }
    else if ($status == Challenge::STATUS_CHALLENGER_WON || $status == Challenge::STATUS_OPPONENT_WON) {
      if ($status == Challenge::STATUS_CHALLENGER_WON) {
        $winner = $info['challenger_champ'];
        $loser = $info['opponent_champ'];
        $winner_prefix = 'challenger';
        $loser_prefix = 'opponent';
        $winner_move = $cmove;
        $loser_move = $omove;
      }
      else {
        $winner = $info['opponent_champ'];
        $loser = $info['challenger_champ'];
        $winner_prefix = 'opponent';
        $loser_prefix = 'challenger';
        $winner_move = $omove;
        $loser_move = $cmove;
      }

      // Check for loss on success.
      if ($this->__check_for_loss_on($winner_move, $winner)) {
        $message[] = $round . $this->__get_move_text($info, $json, $orig_json, $winner_move, $loser_move, Challenge::RESULT_MISS, $winner, $loser);
        $info[$loser_prefix.'_points']++;
      }
      // Check for miss and crit.
      else if ($this->__check_for_miss($info, $status)) {
        $message[] = $round . $this->__get_move_text($info, $json, $orig_json, $winner_move, $loser_move, Challenge::RESULT_MISS, $winner, $loser);
      }
      else if ($this->__check_for_crit($info, $status)) {
        $message[] = $round . $this->__get_move_text($info, $json, $orig_json, $winner_move, $loser_move, Challenge::RESULT_CRIT, $winner, $loser);
        $info[$winner_prefix.'_points'] += 2;
      }
      else {
        $message[] = $round . $this->__get_move_text($info, $json, $orig_json, $winner_move, $loser_move, Challenge::RESULT_NORMAL, $winner, $loser);
        $info[$winner_prefix.'_points']++;
      }
    }

    return implode("\n", $message);
  }

  protected function __get_move_status ($cmove, $omove) {
    // Challenger picked ATTACK.
    if ($cmove == Challenge::MOVE_ATTACK) {
      if ($omove == Challenge::MOVE_ATTACK) {
        return Challenge::STATUS_TIE;
      }
      else if ($omove == Challenge::MOVE_DEFEND) {
        return Challenge::STATUS_OPPONENT_WON;
      }
      else if ($omove == Challenge::MOVE_BREAK) {
        return Challenge::STATUS_CHALLENGER_WON;
      }
    }
    // Challenger picked DEFEND.
    else if ($cmove == Challenge::MOVE_DEFEND) {
      if ($omove == Challenge::MOVE_ATTACK) {
        return Challenge::STATUS_CHALLENGER_WON;
      }
      else if ($omove == Challenge::MOVE_DEFEND) {
        return Challenge::STATUS_TIE;
      }
      else if ($omove == Challenge::MOVE_BREAK) {
        return Challenge::STATUS_OPPONENT_WON;
      }
    }
    // Challenger picked BREAK.
    else if ($cmove == Challenge::MOVE_BREAK) {
      if ($omove == Challenge::MOVE_ATTACK) {
        return Challenge::STATUS_OPPONENT_WON;
      }
      else if ($omove == Challenge::MOVE_DEFEND) {
        return Challenge::STATUS_CHALLENGER_WON;
      }
      else if ($omove == Challenge::MOVE_BREAK) {
        return Challenge::STATUS_TIE;
      }
    }
  }

  protected function __get_as_success_bonus ($move) {
    switch ($move) {
      case Challenge::MOVE_ATTACK:
        return Bonus::ATTACK_AS_SUCCESS;
        break;

      case Challenge::MOVE_DEFEND:
        return Bonus::DEFEND_AS_SUCCESS;
        break;

      case Challenge::MOVE_BREAK:
        return Bonus::BREAK_AS_SUCCESS;
        break;
    }
  }

  protected function __check_tie_breaker_on_fail ($move, $adventurer) {
    return (rand(1, 100) <= $adventurer->get_bonus()->get_mod(Bonus::TIE_BREAKER_ON_FAIL, 'Challenge->'.$move, Bonus::MOD_HUNDREDS));
  }

  protected function __check_for_loss_on ($move, $adventurer) {
    return (rand(1, 100) <= $adventurer->get_bonus()->get_mod(Bonus::LOSS_ON_SUCCESS, 'Challenge->'.$move, Bonus::MOD_HUNDREDS));
  }

  protected function __check_for_miss (&$info, $winner) {
    // Check for a miss rate.
    if ($winner == 'challenger')
      $rate = $this->__calc_miss_rate($info['challenger_champ'], $info['opponent_champ']);
    else
      $rate = $this->__calc_miss_rate($info['opponent_champ'], $info['challenger_champ']);

    // If there's no miss rate, they didn't miss.
    if ($rate <= 0) return FALSE;

    // There's a miss rate, so check if they missed.
    return (rand(1, 100) <= $rate);
  }

  protected function __check_for_crit (&$info, $winner) {
    // Check for a crit rate.
    if ($winner == 'challenger')
      $rate = $this->__calc_crit_rate($info['challenger_champ'], $info['opponent_champ']);
    else
      $rate = $this->__calc_crit_rate($info['opponent_champ'], $info['challenger_champ']);

    // If there's no crit rate, they didn't crit.
    if ($rate <= 0) return FALSE;

    // There's a crit rate, so check if they missed.
    return (rand(1, 100) <= $rate);
  }

  protected function __calc_miss_rate ($first, $second) {
    // Check difference of level.
    $rate = 4 * max(0, $second->get_level(false) - $first->get_level(false));
    // Add in bonuses.
    $rate += $first->get_bonus()->get_mod(Bonus::MISS_RATE, Bonus::FOR_DEFAULT, Bonus::MOD_HUNDREDS);
    // Add in any opponent-imposed changes.
    $rate += $second->get_bonus()->get_mod(Bonus::OPPONENT_MISS_RATE, Bonus::FOR_DEFAULT, Bonus::MOD_HUNDREDS);

    return $rate;
  }

  protected function __calc_crit_rate ($first, $second) {
    // Check difference of level.
    $rate = 2 * max(0, $first->get_level(false) - $second->get_level(false));
    // Add in bonuses.
    $rate += $first->get_bonus()->get_mod(Bonus::CRIT_RATE, Bonus::FOR_DEFAULT, Bonus::MOD_HUNDREDS);
    // Add in any opponent-imposed changes.
    $rate += $second->get_bonus()->get_mod(Bonus::OPPONENT_CRIT_RATE, Bonus::FOR_DEFAULT, Bonus::MOD_HUNDREDS);

    return $rate;
  }

  protected function __get_move_text (&$info, &$json, &$orig_json, $winner_move, $loser_move, $move_result, $winner_champ, $loser_champ) {
    // Refresh the text list if it's empty.
    if (count($json[$winner_move][$loser_move][$move_result]) <= 0) {
      $json[$winner_move][$loser_move][$move_result] = $orig_json[$winner_move][$loser_move][$move_result];
    }
    // Get the text.
    $index = array_rand($json[$winner_move][$loser_move][$move_result]);
    $text = $json[$winner_move][$loser_move][$move_result][$index];
    // Remove the entry so it doesn't get re-used in the same challenge.
    array_splice($json[$winner_move][$loser_move][$move_result], $index, 1);
    
    // Replace the tokens with the appropriate text.
    $winner_name = $winner_champ->get_display_name(false, false, true, false, false);
    $loser_name = $loser_champ->get_display_name(false, false, true, false, false);
    $tokens = array(
      "!wchamp's" => '*'. $winner_name . $winner_champ->get_possessive() .'*',
      "!wchamp" => '*'. $winner_name .'*',
      "!wgender" => $winner_champ->get_gender(),
      "!wpronoun" => $winner_champ->get_pronoun(),
      "!wposspronoun" => $winner_champ->get_possessive_pronoun(),
      "!wotherpronoun" => $winner_champ->get_other_pronoun(),
      "!lchamp's" => '*'. $loser_name . $loser_champ->get_possessive() .'*',
      "!lchamp" => '*'. $loser_name .'*',
      "!lgender" => $loser_champ->get_gender(),
      "!lpronoun" => $loser_champ->get_pronoun(),
      "!lposspronoun" => $loser_champ->get_possessive_pronoun(),
      "!lotherpronoun" => $loser_champ->get_other_pronoun(),
    );

    return str_replace(array_keys($tokens), array_values($tokens), $text);
  }



  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function moves () {
    return array(Challenge::MOVE_ATTACK, Challenge::MOVE_DEFEND, Challenge::MOVE_BREAK);
  }

  public static function valid_move ($move) {
    return in_array($move, Challenge::moves());
  }

  public static function convert_moves_to_list ($moves) {
    if (empty($moves) || !is_string($moves)) return array();
    return explode(',', $moves);
  }

  public static function convert_list_to_moves ($list) {
    if (!is_array($list) || empty($list)) return '';
    return implode(',', $list);
  }

  /**
   * Load up the list of Challenge texts.
   */
  public static function load_texts_list ($original = false) {
    $file_name = RPG_SERVER_ROOT .($original ? Challenge::FILENAME_TEXTS_ORIGINAL : Challenge::FILENAME_TEXTS);
    $json_string = file_get_contents($file_name);
    return json_decode($json_string, true);
  }

  /**
   * $data -> An array that can be properly encoded using PHP's json_encode function.
   */
  public static function save_texts_list ($data) {
    // Write out the JSON file to prevent names from being reused.
    $fp = fopen(RPG_SERVER_ROOT . Challenge::FILENAME_TEXTS, 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
  }

  /**
   * Replace the working texts list with a copy of the original.
   */
  public static function refresh_original_texts_list () {
    // Load the original JSON list.
    $json = Challenge::load_texts_list(true);

    // Overwrite the working copy with the new list.
    Challenge::save_texts_list($json);

    return $json;
  }
}