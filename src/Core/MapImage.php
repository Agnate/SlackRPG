<?php

class MapImage {
  
  public $url;
  public $map;

  const DEFAULT_IMAGE_URL = '/images/map.png';

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
    // Create base image.
    $num_rows = Map::NUM_ROWS;
    $num_cols = Map::NUM_COLS;

    $icon_size = 22;
    $cell_size = $icon_size * 2;
    $width = $num_rows * $cell_size;
    $height = $num_cols * $cell_size;

    // Create the initial image.
    $image = imagecreatetruecolor($width, $height);
    $icon_url = RPG_SERVER_ROOT.'/icons';

    // Load the required images to blend in.
    $revealed = imagecreatefrompng($icon_url.'/revealed.png');
    $hidden = imagecreatefrompng($icon_url.'/hidden.png');

    // Generate the base.
    for ($r = 0; $r < $num_rows; $r++) {
      for ($c = 0; $c < $num_rows; $c++) {
        $x = $r * $cell_size;
        $y = $c * $cell_size;
        MapImage::create_cell($image, $revealed, $x, $y, $icon_size);
      }
    }

    // Generate the fog of war.
    $locations = $map->get_locations();
    foreach ($locations as $location) {
      if ($location->revealed) continue;
      $x = ($location->row - 1) * $cell_size;
      $y = ($location->col - 1) * $cell_size;
      MapImage::create_cell($image, $hidden, $x, $y, $icon_size, 75);
    }

    // Output the image.
    $image_url = MapImage::DEFAULT_IMAGE_URL;
    $file_path = RPG_SERVER_ROOT.'/public'.$image_url;
    imagepng($image, $file_path);

    // Create the object.
    return new MapImage (array('map' => $map, 'url' => $image_url));
  }

  /**
   * Creates icons in this order:  top-left, top-right, bottom-left, bottom-right.
   */
  protected static function create_cell (&$image, $icons, $x, $y, $icon_size, $opacity = 100) {
    if (!is_array($icons)) $icons = array($icons, $icons, $icons, $icons);
    imagecopymerge($image, $icons[0], $x, $y, 0, 0, $icon_size, $icon_size, $opacity);
    imagecopymerge($image, $icons[1], $x+$icon_size, $y, 0, 0, $icon_size, $icon_size, $opacity);
    imagecopymerge($image, $icons[2], $x, $y+$icon_size, 0, 0, $icon_size, $icon_size, $opacity);
    imagecopymerge($image, $icons[3], $x+$icon_size, $y+$icon_size, 0, 0, $icon_size, $icon_size, $opacity);
  }
  
}