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
    // Create base image with extra row and col for letters and numbers.
    $num_rows = Map::NUM_ROWS + 1;
    $num_cols = Map::NUM_COLS + 1;

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
        $x = $r * $cell_size;
        $y = $c * $cell_size;
        MapImage::create_random_cell($image, $spritesheet, $sheet['tiles']['grass'], $x, $y);
      }
    }

    // Generate the custom icons and fog of war.
    $locations = $map->get_locations();
    $capitals = array();
    foreach ($locations as $location) {
      // If these are the capitals, store them and do this after (complicated).
      if ($location->type == Location::TYPE_CAPITAL) {
        $capitals[] = $location;
        continue;
      }

      $x = $location->col * $cell_size;
      $y = $location->row * $cell_size;

      // Fancy icon.
      if ($location->revealed) {

      }
      // Fog of war.
      else {
        MapImage::create_random_cell($image, $spritesheet, $sheet['tiles']['fog'], $x, $y, 75);
      }
    }

    // Organize and render the capital.
    $capital_info = array(
      'row' => 9999999,
      'col' => 9999999,
    );
    // Get the row and col closest to origin.
    foreach ($capitals as $location) {
      if ($location->row < $capital_info['row']) $capital_info['row'] = $location->row;
      if ($location->col < $capital_info['col']) $capital_info['col'] = $location->col;
    }
    // Render the capital.
    $count = 0;
    for ($r = $capital_info['row']; $r <= $capital_info['row']+1; $r++) {
      for ($c = $capital_info['col']; $c <= $capital_info['col']+1; $c++) {
        $x = $c * $cell_size;
        $y = $r * $cell_size;
        $count++;
        MapImage::create_cell($image, $spritesheet, $sheet['tiles']['capital'.$count], $x, $y);
      }
    }

    // Draw grid lines and letters/numbers.
    for ($r = 1; $r <= $num_rows; $r++) {
      $y = $r * $cell_size;
      imageline($image, ($cell_size / 4) * 3, $y, $width, $y, $gray);
      // Numbers
      $x = ($r < 10) ? 27 : 2;
      imagettftext($image, 30, 0, $x, $y + $cell_size - 16, $black, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', $r);
    }

    for ($c = 1; $c <= $num_cols; $c++) {
      $x = $c * $cell_size;
      imageline($image, $x, ($cell_size / 4) * 3, $x, $height, $gray);
      // Letters
      imagettftext($image, 30, 0, $x + 20, $cell_size - 10, $black, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', Location::get_letter($c));
    }

    // Output the image.
    $image_url = MapImage::DEFAULT_IMAGE_URL;
    $file_path = RPG_SERVER_ROOT.'/public'.$image_url;
    imagepng($image, $file_path);

    // Create the object.
    return new MapImage (array('map' => $map, 'url' => $image_url));
  }

  protected static function create_random_cell (&$image, $icon_image, $icon_options, $x, $y, $opacity = 100) {
    $tiles = array();
    // Select 4 tiles.
    for ($t = 1; $t <= 4; $t++) {
      $tiles[] = $icon_options[array_rand($icon_options)];
    }

    return MapImage::create_cell($image, $icon_image, $tiles, $x, $y, $opacity);
  }

  /**
   * Creates icons in this order:  top-left, top-right, bottom-left, bottom-right.
   */
  protected static function create_cell (&$image, $icon_image, $icons, $x, $y, $opacity = 100) {
    $icon_size = $icons[0]['width'];

    $coords = array(
      array('x' => $x, 'y' => $y),
      array('x' => $x+$icon_size, 'y' => $y),
      array('x' => $x, 'y' => $y+$icon_size),
      array('x' => $x+$icon_size, 'y' => $y+$icon_size),
    );

    for ($i = 0; $i < 4; $i++) {
      if ($opacity == 100) {
        imagecopy($image, $icon_image, $coords[$i]['x'], $coords[$i]['y'], $icons[$i]['x'], $icons[$i]['y'], $icons[$i]['width'], $icons[$i]['height']);
      }
      else {
        imagecopymerge($image, $icon_image, $coords[$i]['x'], $coords[$i]['y'], $icons[$i]['x'], $icons[$i]['y'], $icons[$i]['width'], $icons[$i]['height'], $opacity);
      }
    }
  }

  
  
}