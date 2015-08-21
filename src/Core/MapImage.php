<?php

class MapImage {
  
  public $url;
  public $map;

  const DEFAULT_IMAGE_URL = '/images/map.png';
  const DEFAULT_ICON_URL = '/icons';
  
  
  function __construct($data = array()) {
    // Save values to object.
    if (count($data)) foreach ($data as $key => $value) if (property_exists($this, $key)) $this->{$key} = $value;

    if (empty($this->url)) $this->url = MapImage::DEFAULT_IMAGE_URL;
  }


  /* =================================
     ______________  ________________
    / ___/_  __/   |/_  __/  _/ ____/
    \__ \ / / / /| | / /  / // /     
   ___/ // / / ___ |/ / _/ // /___   
  /____//_/ /_/  |_/_/ /___/\____/   
                                     
  ==================================== */

  public static function generate_image ($map) {
    // Get all locations for this map.
    $locations = $map->get_locations();
    $loc_coords = array();

    // Figure out the row and col info.
    $info = array(
      'row_lo' => Map::CAPITAL_START_ROW - Map::MIN_ROWS,
      'row_hi' => Map::CAPITAL_START_ROW + Map::MIN_ROWS,
      'col_lo' => Map::CAPITAL_START_COL - Map::MIN_COLS,
      'col_hi' => Map::CAPITAL_START_COL + Map::MIN_COLS,
    );

    foreach ($locations as $location) {
      if ($location->row > $info['row_hi']) $info['row_hi'] = $location->row;
      if ($location->row < $info['row_lo']) $info['row_lo'] = $location->row;
      if ($location->col > $info['col_hi']) $info['col_hi'] = $location->col;
      if ($location->col < $info['col_lo']) $info['col_lo'] = $location->col;
      // Save the location to coordinate map.
      if (!isset($loc_coords[$location->row])) $loc_coords[$location->row] = array();
      $loc_coords[$location->row][$location->col] = $location;
    }

    // Fill in the blanks in the loc_coords.
    for ($r = $info['row_lo']; $r <= $info['row_hi']; $r++) {
      for ($c = $info['col_lo']; $c <= $info['col_hi']; $c++) {
        if (!isset($loc_coords[$r])) $loc_coords[$r] = array();
        if (!isset($loc_coords[$r][$c])) $loc_coords[$r][$c] = true;
      }
    }

    // Create base image with extra row and col for letters and numbers.
    $num_rows = ($info['row_hi'] - $info['row_lo'] + 1) + 1;
    $num_cols = ($info['col_hi'] - $info['col_lo'] + 1) + 1;

    $icon_size = 32;
    $cell_size = $icon_size * 2;
    $width = $num_cols * $cell_size;
    $height = $num_rows * $cell_size;

    // Create the initial image.
    $image = imagecreatetruecolor($width+1, $height+1);

    // Set some colours.
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    $gray = imagecolorallocate($image, 80, 80, 80);

    // Fill the background.
    imagefill($image, 0, 0, $white);
    
    // Load the spritesheet.
    $sheet = SpriteSheet::load_sprites_list();
    $spritesheet = imagecreatefrompng(RPG_SERVER_ROOT. $sheet['url']);

    // Generate the base (randomized grass).
    for ($r = 1; $r <= $num_rows; $r++) {
      for ($c = 1; $c <= $num_cols; $c++) {
        $x = $c * $cell_size;
        $y = $r * $cell_size;
        MapImage::create_random_cells($image, $icon_size, $spritesheet, reset($sheet['tiles']['grass']), $x, $y);
      }
    }

    foreach ($loc_coords as $row => $cols) {
      foreach ($cols as $col => $location) {
        $x = ($col - $info['col_lo'] + 1) * $cell_size;
        $y = ($row - $info['row_lo'] + 1) * $cell_size;

        // If there's no location (or we can't go to the location), do Fog of war.
        if ($location === true || ($location->revealed == false && $location->open == false)) {
          MapImage::create_random_cells($image, $icon_size, $spritesheet, reset($sheet['tiles']['fog']), $x, $y, 90);
        }
        // Lite fog of war.
        else if ($location->revealed == false && $location->open) {
          MapImage::create_random_cells($image, $icon_size, $spritesheet, reset($sheet['tiles']['fog']), $x, $y, 60);
        }
        // Fancy icon.
        else if ($location->revealed && $location->type != Location::TYPE_EMPTY) {
          $icon = MapImage::generalize_icon($location->get_map_icon());
          // Check if we have an icon for this.
          if (isset($sheet['tiles'][$icon])) {
            // If there's never been an icon generated, pick one and save it.
            if (empty($location->map_icon)) {
              $location->map_icon = array_rand($sheet['tiles'][$icon]);
              $location->save();
            }
            // Get the tile we're rendering out.
            $fancy_tiles = $sheet['tiles'][$icon][$location->map_icon];
            MapImage::create_random_cells($image, $icon_size, $spritesheet, $fancy_tiles, $x, $y, 100, false);
          }
          else {
            $fancy_tiles = reset($sheet['tiles']['unknown']);
            MapImage::create_random_cells($image, $icon_size, $spritesheet, $fancy_tiles, $x, $y);
          }
        }
      }
    }

    // Draw grid lines and letters/numbers.
    for ($r = 1; $r <= $num_rows; $r++) {
      $y = $r * $cell_size;
      imageline($image, ($cell_size / 4) * 3, $y, $width, $y, $gray);
      // Numbers
      $row_num = $info['row_lo'] + $r - 1;
      $x = ($row_num < 10) ? 41 : (($row_num < 100) ? 22 : 0);
      imagettftext($image, 26, 0, $x, $y + $cell_size - 16, $black, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', $row_num);
    }

    for ($c = 1; $c <= $num_cols; $c++) {
      $x = $c * $cell_size;
      imageline($image, $x, ($cell_size / 4) * 3, $x, $height, $gray);
      // Letters
      $col_num = $info['col_lo'] + $c - 1;
      imagettftext($image, 30, 0, $x + 10, $cell_size - 10, $black, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', Location::get_letter($col_num));
    }

    // Output the image.
    $image_url = MapImage::DEFAULT_IMAGE_URL;
    $file_path = RPG_SERVER_ROOT.'/public'.$image_url;
    imagepng($image, $file_path);

    // Create the object.
    return new MapImage (array('map' => $map, 'url' => $image_url));
  }

  protected static function generalize_icon ($icon) {
    $list = array();
    $list['arch'] = array('arch');
    $list['beanstalk'] = array('beanstalk');
    $list['bridge'] = array('bridge');
    $list['canyon'] = array('canyon', 'gulch', 'gorge', 'ravine', 'crevice', 'chasm', 'ridge', 'glen', 'cleft', 'crag', 'bluff', 'abyss');
    $list['capital'] = array('capital');
    $list['castle'] = array('castle', 'fort', 'palace', 'fortress', 'stronghold', 'keep', 'citadel', 'empire', 'kingdom');
    $list['cave'] = array('dungeon', 'cave', 'lair', 'cavern', 'hollow', 'den', 'hole', 'tunnels', 'hideout', 'grotto');
    $list['church'] = array('cathedral', 'church', 'sanctuary', 'library');
    $list['city'] = array('city', 'metropolis');
    $list['crater'] = array('crater', 'pits', 'pit', 'comet');
    $list['crystal'] = array('crystal', 'mineral');
    $list['desert'] = array('desert', 'flatland', 'savanna', 'wasteland', 'barrens', 'expanse', 'dunes');
    $list['estate'] = array('ranch', 'estate', 'quarters', 'mansion');
    $list['farm'] = array('farm');
    $list['field'] = array('field', 'meadow', 'lowland', 'grassland', 'valley', 'vale', 'moor', 'heath', 'prairie', 'steppes');
    $list['flowers'] = array('flower', 'flowers', 'flower field');
    $list['forest'] = array('forest', 'thicket', 'brier', 'weald', 'dell', 'grove', 'coppice', 'glade', 'orchard', 'wilds');
    $list['fossils'] = array('fossils', 'bones', 'remains');
    $list['graveyard'] = array('graveyard', 'barrow', 'tomb', 'cemetery');
    $list['hill'] = array('hill', 'knoll', 'hillock', 'foothills');
    $list['hut'] = array('hut', 'witch hut');
    $list['jungle'] = array('jungle');
    $list['lake'] = array('lake', 'river', 'stream', 'brook', 'creek', 'rill', 'basin', 'spring', 'loch', 'shallows', 'strand', 'cove', 'fjord', 'waterfall');
    $list['lava'] = array('lava lake', 'magma pool');
    $list['outpost'] = array('outpost', 'frontier', 'garrison');
    $list['mausoleum'] = array('crypt', 'mausoleum', 'sepulcher', 'catacomb', 'necropolis', 'cairn', 'dolmen');
    $list['maze'] = array('maze', 'labyrinth');
    $list['mesa'] = array('mesa');
    $list['mine'] = array('mine', 'abandoned mine');
    $list['moai'] = array('moai');
    $list['mountain'] = array('mountain', 'summit', 'pass', 'point', 'tor');
    $list['oasis'] = array('oasis');
    $list['obelisk'] = array('obelisk');
    $list['pillar'] = array('pillar', 'spire', 'monolith');
    $list['portal'] = array('portal', 'gateway');
    $list['prison'] = array('bastille', 'prison');
    $list['pyramid'] = array('pyramid');
    $list['ruin'] = array('ruin', 'ruins', 'castle ruins', 'fortress ruins');
    $list['shrine'] = array('shrine', 'dias');
    $list['stone'] = array('standing', 'stones', 'stone', 'menhir', 'rock');
    $list['statue'] = array('statue', 'statues');
    $list['swamp'] = array('swamp', 'quagmire', 'mire', 'fen', 'bog', 'marsh', 'wetland', 'lagoon');
    $list['throne'] = array('throne');
    $list['tree'] = array('tree', 'hollow tree');
    $list['tundra'] = array('tundra', 'taiga');
    $list['tower'] = array('tower', 'lookout');
    $list['town'] = array('town', 'village', 'enclave', 'borough');
    $list['vault'] = array('vault');
    $list['volcano'] = array('volcano');
    $list['wall'] = array('wall', 'walls');

    // If the icon is generalized in one of lists, return the generalized key.
    foreach ($list as $map_icon => $alts) {
      if (in_array($icon, $alts)) return $map_icon;
    }

    return $icon;
  }

  protected static function create_random_cells (&$image, $tile_size, $icon_image, $icon_options, $x, $y, $opacity = 100, $fill_all = true) {
    $tiles = array();
    // Select 4 tiles randomly.
    if ($fill_all) {
      for ($t = 1; $t <= 4; $t++) {
        $tiles[] = $icon_options[array_rand($icon_options)];
      }
    }
    // If there's only 1, centre it.
    // else if (count($icon_options) == 1) {
    //   return MapImage::create_centered_cell($image, $tile_size, $icon_image, $icon_options[0], $x, $y, $opacity);
    // }
    // Grab them in order.
    else {
      foreach ($icon_options as $tile) {
        $tiles[] = $tile;
      }
    }

    return MapImage::create_cell($image, $tile_size, $icon_image, $tiles, $x, $y, $opacity);
  }

  /**
   * Creates icons in this order:  top-left, top-right, bottom-left, bottom-right.
   */
  protected static function create_cell (&$image, $tile_size, $icon_image, $icons, $x, $y, $opacity = 100, $centered = true) {
    $num_icons = count($icons);
    if ($num_icons <= 0) return;

    $coords = array(
      array('x' => $x, 'y' => $y),
      array('x' => $x+$tile_size, 'y' => $y),
      array('x' => $x, 'y' => $y+$tile_size),
      array('x' => $x+$tile_size, 'y' => $y+$tile_size),
    );

    // Change the coord structure if they are centered.
    if ($centered) {
      $cell_size = $tile_size * 2;
      $x_centered = $x + floor(($cell_size - $icons[0]['width']) / 2);
      $y_centered = $y + floor(($cell_size - $icons[0]['height']) / 2);

      switch ($num_icons) {
        case 1:
          $coords = array(
            array('x' => $x_centered, 'y' => $y_centered)
          );
          break;

        case 2:
          if ($icons[0]['orientation'] == 'h') {
            $coords = array(
              array('x' => $x, 'y' => $y_centered),
              array('x' => $x+$tile_size, 'y' => $y_centered),
            );
          }
          else if ($icons[0]['orientation'] == 'v') {
            $coords = array(
              array('x' => $x_centered, 'y' => $y),
              array('x' => $x_centered, 'y' => $y+$tile_size),
            );
          }
          break;
      }
    }

    $num_coords = count($coords);
    for ($i = 0; $i < $num_coords; $i++) {
      if (!isset($icons[$i])) continue;

      if ($opacity == 100) {
        imagecopy($image, $icon_image, $coords[$i]['x'], $coords[$i]['y'], $icons[$i]['x'], $icons[$i]['y'], $icons[$i]['width'], $icons[$i]['height']);
      }
      else {
        imagecopymerge($image, $icon_image, $coords[$i]['x'], $coords[$i]['y'], $icons[$i]['x'], $icons[$i]['y'], $icons[$i]['width'], $icons[$i]['height'], $opacity);
      }
    }
  }

  /**
   * Centres a single icon in the middle of 4 tiles.
   */
  protected static function create_centered_cell (&$image, $tile_size, $icon_image, $icons, $x, $y, $opacity = 100) {
    $cell_size = $tile_size * 2;
    
    $x += floor(($cell_size - $icon['width']) / 2);
    $y += floor(($cell_size - $icon['height']) / 2);

    if ($opacity == 100) {
      imagecopy($image, $icon_image, $x, $y, $icon['x'], $icon['y'], $icon['width'], $icon['height']);
    }
    else {
      imagecopymerge($image, $icon_image, $x, $y, $icons[$i]['x'], $icons[$i]['y'], $icons[$i]['width'], $icons[$i]['height'], $opacity);
    }
  }
}