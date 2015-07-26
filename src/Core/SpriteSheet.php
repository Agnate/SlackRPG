<?php

class SpriteSheet {
  
  const DEBUG_URL = '/debug/sprites.png';
  const DEFAULT_SPRITESHEET_URL = '/icons/sprites.png';
  const DEFAULT_ICON_URL = '/icons/rough';

  const FILENAME_LIST = '/icons/sprites.json';

  
  public static function generate ($debug = false) {
    // Get all the icons and merge into a single list.
    $all = SpriteSheet::all();
    $json = array(
      'url' => '',
      'tiles' => array(),
    );

    // Count how many tiles we have and separate into a useful list.
    $tile_size = 32;
    $num_tiles = count($all);
    $num_cols = 20;
    $num_rows = ceil($num_tiles / $num_cols);
    $width = $num_cols * $tile_size;
    $height = $num_rows * $tile_size;

    // Create the transparent initial image.
    $image = imagecreatetruecolor($width, $height);
    imagealphablending($image, true);
    imagesavealpha($image, true);
    $trans_colour = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $trans_colour);
    
    // Create the sprite sheet.
    $row = 0;
    $col = 0;
    foreach ($all as $type => $tiles) {
      if (!isset($json['tiles'][$type])) $json['tiles'][$type] = array();

      foreach ($tiles as $tile) {
        $x = $col * $tile['width'];
        $y = $row * $tile['height'];
        imagecopy($image, $tile['image'], $x, $y, $tile['x'], $tile['y'], $tile['width'], $tile['height']);

        // Store new coordinates.
        $json['tiles'][$type][] = array(
          'x' => $x,
          'y' => $y,
          'width' => $tile['width'],
          'height' => $tile['height'],
        );

        $col++;
        if ($col > $num_cols) {
          $col = 0;
          $row++;
        }
      }
    }

    // Output the image.
    $image_url = SpriteSheet::DEFAULT_SPRITESHEET_URL;
    $file_path = RPG_SERVER_ROOT.$image_url;
    imagepng($image, $file_path);
    $urls = array('url' => $image_url);

    // Save out JSON data for sprites.
    $json['url'] = $image_url;
    SpriteSheet::save_sprites_list($json);

    // If we're debugging, also output to the public area.
    if ($debug) {
      $debug_url = SpriteSheet::DEBUG_URL;
      $debug_file_path = RPG_SERVER_ROOT .'/public'.$debug_url;
      imagepng($image, $debug_file_path);
      $urls['debug'] = $debug_url;
    }

    // Create the object.
    return $urls;
  }

  protected static function all () {
    $tile_size = 32;

    $sprite_locations = array(
      'terrain' => array(
        'image' => SpriteSheet::png('/terrain.png'),
        'tiles' => array(
          'grass' => array(
            array('x' => 22, 'y' => 3),
            // array('x' => 21, 'y' => 5),
            array('x' => 22, 'y' => 5),
            array('x' => 23, 'y' => 5),
          ),
          'fog' => array(
            array('x' => 29, 'y' => 5),
          ),
        ),
      ),
      'capital' => array(
        'image' => SpriteSheet::png('/capital.png'),
        'tiles' => array(
          'capital1' => array(
            array('x' => 0, 'y' => 2),
            array('x' => 1, 'y' => 2),
            array('x' => 0, 'y' => 3),
            array('x' => 1, 'y' => 3),
          ),
          'capital2' => array(
            array('x' => 2, 'y' => 2),
            array('x' => 3, 'y' => 2),
            array('x' => 2, 'y' => 3),
            array('x' => 3, 'y' => 3),
          ),
          'capital3' => array(
            array('x' => 0, 'y' => 4),
            array('x' => 1, 'y' => 4),
            array('x' => 0, 'y' => 5),
            array('x' => 1, 'y' => 5),
          ),
          'capital4' => array(
            array('x' => 2, 'y' => 4),
            array('x' => 3, 'y' => 4),
            array('x' => 2, 'y' => 5),
            array('x' => 3, 'y' => 5),
          ),
        ),
      ),
    );

    // Set width and height if they're not set.
    $all = array();
    foreach ($sprite_locations as &$list) {
      // Do extra math and merge into single list.
      foreach ($list['tiles'] as $type => &$tiles) {
        if (!isset($all[$type])) $all[$type] = array();
        foreach ($tiles as &$tile) {
          $tile['x'] = $tile['x'] * $tile_size;
          $tile['y'] = $tile['y'] * $tile_size;
          if (!isset($tile['width'])) $tile['width'] = $tile_size;
          if (!isset($tile['height'])) $tile['height'] = $tile_size;
          $tile['image'] =& $list['image'];
          $tile['type'] = $type;
          $all[$type][] = $tile;
        }
      }
    }

    return $all;
  }

  protected static function png ($local_url) {
    return imagecreatefrompng(SpriteSheet::url($local_url));
  }
  protected static function jpg ($local_url) {
    return imagecreatefromjpeg(SpriteSheet::url($local_url));
  }
  protected static function url ($end) {
    return RPG_SERVER_ROOT. SpriteSheet::DEFAULT_ICON_URL .$end;
  }

  /**
   * Load up the list of sprites that are still available.
   */
  public static function load_sprites_list () {
    $file_name = RPG_SERVER_ROOT . SpriteSheet::FILENAME_LIST;
    $json_string = file_get_contents($file_name);
    return json_decode($json_string, true);
  }

  /**
   * $data -> An array that can be properly encoded using PHP's json_encode function.
   */
  protected static function save_sprites_list ($data) {
    // Write out the JSON file to store the info.
    $fp = fopen(RPG_SERVER_ROOT . SpriteSheet::FILENAME_LIST, 'w');
    fwrite($fp, json_encode($data));
    fclose($fp);
  }
}