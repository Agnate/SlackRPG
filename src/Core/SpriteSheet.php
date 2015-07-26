<?php

class SpriteSheet {
  
  const DEBUG_URL = '/debug/sprites.png';
  const DEBUG_LINED_FOLDER = '/debug/lined';
  const DEFAULT_SPRITESHEET_URL = '/icons/sprites.png';
  const DEFAULT_ICON_URL = '/icons/rough';
  const DEFAULT_LINED_URL = '/icons/lined';

  const FILENAME_LIST = '/icons/sprites.json';

  
  public static function generate ($debug = false) {
    // Get all the icons and merge into a single list.
    $all = SpriteSheet::all();
    $json = array(
      'url' => '',
      'tiles' => array(),
    );

    // Count the tiles.
    $num_tiles = 0;
    foreach ($all as $tile_group) {
      foreach ($tile_group as $tiles) {
        $num_tiles += count($tiles);
      }
    }

    // Count how many tiles we have and separate into a useful list.
    $tile_size = 32;
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
    foreach ($all as $type => $tile_group) {
      if (!isset($json['tiles'][$type])) $json['tiles'][$type] = array();

      foreach ($tile_group as $tiles) {
        $group = array();

        foreach ($tiles as $tile) {
          $x = $col * $tile['width'];
          $y = $row * $tile['height'];
          imagecopy($image, $tile['image'], $x, $y, $tile['x'], $tile['y'], $tile['width'], $tile['height']);

          // Store new coordinates.
          $group[] = array(
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

        $json['tiles'][$type][] = $group;
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

  public static function add_grid_to_sheet ($local_url, $debug = false) {
    // Get the image information.
    $info = getimagesize(SpriteSheet::url($local_url));
    $tile_size = 32;
    $width = $info[0] + $tile_size;
    $height = $info[1] + $tile_size;

    // Figure out number of rows and columns.
    $num_rows = ceil($height / $tile_size);
    $num_cols = ceil($width / $tile_size);

    // Create the transparent initial image.
    $image = imagecreatetruecolor($width+1, $height+1);
    imagealphablending($image, true);
    imagesavealpha($image, true);
    $trans_colour = imagecolorallocatealpha($image, 0, 0, 0, 127);
    imagefill($image, 0, 0, $trans_colour);

    // Add colours.
    $gray = imagecolorallocate($image, 80, 80, 80);

    // Copy over the image we grabbed.
    $original_image = SpriteSheet::png($local_url);
    imagecopy($image, $original_image, $tile_size, $tile_size, 0, 0, $width, $height);

    // Add grid lines to make life easier.
    for ($r = 1; $r <= $num_rows; $r++) {
      $y = $r * $tile_size;
      imageline($image, ($tile_size - 6), $y, $width, $y, $gray);
      // Numbers
      if ($r == $num_rows) continue;
      $row = $r-1;
      $x = ($row < 10) ? 13 : 4;
      imagettftext($image, 12, 0, $x, $y + 6, $gray, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', $row);
    }

    for ($c = 1; $c <= $num_cols; $c++) {
      $x = $c * $tile_size;
      imageline($image, $x, ($tile_size - 6), $x, $height, $gray);
      // Numbers
      if ($c == $num_cols) continue;
      $col = $c - 1;
      $x = ($col < 10) ? $x-4 : $x-10;
      imagettftext($image, 12, 0, $x, $tile_size - 10, $gray, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', $col);
    }

    // Save it back out.
    $image_url = SpriteSheet::lined_url($local_url, false);
    $file_path = SpriteSheet::lined_url($local_url);
    imagepng($image, $file_path);
    $urls = array('url' => $image_url);

    // If we're debugging, also output to the public area.
    if ($debug) {
      $debug_url = SpriteSheet::DEBUG_LINED_FOLDER. $local_url;
      $debug_file_path = RPG_SERVER_ROOT .'/public'.$debug_url;
      imagepng($image, $debug_file_path);
      $urls['debug'] = $debug_url;
    }

    return $urls;
  }

  protected static function all () {
    $tile_size = 32;

    $sprite_locations = array(
      'terrain' => array(
        'image' => SpriteSheet::png('/terrain.png'),
        'tiles' => array(
          'grass' => array(
            array(
              array('x' => 22, 'y' => 3),
              array('x' => 22, 'y' => 5),
              array('x' => 23, 'y' => 5),
            ),
          ),
          'fog' => array(
            array(
              array('x' => 29, 'y' => 5),
            ),
          ),
        ),
      ),
      'capital' => array(
        'image' => SpriteSheet::png('/capital.png'),
        'tiles' => array(
          'capital' => array(
            array(
              array('x' => 0, 'y' => 2),
              array('x' => 1, 'y' => 2),
              array('x' => 0, 'y' => 3),
              array('x' => 1, 'y' => 3),
            ),
            array(
              array('x' => 2, 'y' => 2),
              array('x' => 3, 'y' => 2),
              array('x' => 2, 'y' => 3),
              array('x' => 3, 'y' => 3),
            ),
            array(
              array('x' => 0, 'y' => 4),
              array('x' => 1, 'y' => 4),
              array('x' => 0, 'y' => 5),
              array('x' => 1, 'y' => 5),
            ),
            array(
              array('x' => 2, 'y' => 4),
              array('x' => 3, 'y' => 4),
              array('x' => 2, 'y' => 5),
              array('x' => 3, 'y' => 5),
            ),            
          ),
          'estate' => array(
            array(
              array('x' => 4, 'y' => 2),
            ),
          ),
          'castle' => array(
            array(
              array('x' => 5, 'y' => 0),
            ),
            array(
              array('x' => 5, 'y' => 1),
            ),
          ),
          'city' => array(
            array(
              array('x' => 8, 'y' => 2),
              array('x' => 9, 'y' => 2),
              array('x' => 8, 'y' => 3),
              array('x' => 9, 'y' => 3),
            ),
          ),
          'town' => array(
            array(
              array('x' => 12, 'y' => 2),
              array('x' => 13, 'y' => 2),
              array('x' => 12, 'y' => 3),
              array('x' => 13, 'y' => 3),
            ),
            array(
              array('x' => 14, 'y' => 2),
              array('x' => 15, 'y' => 2),
              array('x' => 14, 'y' => 3),
              array('x' => 15, 'y' => 3),
            ),
            array(
              array('x' => 12, 'y' => 4),
              array('x' => 13, 'y' => 4),
              array('x' => 12, 'y' => 5),
              array('x' => 13, 'y' => 5),
            ),
            array(
              array('x' => 14, 'y' => 4),
              array('x' => 15, 'y' => 4),
              array('x' => 14, 'y' => 5),
              array('x' => 15, 'y' => 5),
            ),
          ),
        ),
      ),
    );

    // Set width and height if they're not set.
    $all = array();
    foreach ($sprite_locations as &$list) {
      // Do extra math and merge into single list.
      foreach ($list['tiles'] as $type => &$tile_group) {
        if (!isset($all[$type])) $all[$type] = array();

        foreach ($tile_group as &$tiles) {
          $group = array();
          
          foreach ($tiles as &$tile) {
            $tile['x'] = $tile['x'] * $tile_size;
            $tile['y'] = $tile['y'] * $tile_size;
            if (!isset($tile['width'])) $tile['width'] = $tile_size;
            if (!isset($tile['height'])) $tile['height'] = $tile_size;
            $tile['image'] =& $list['image'];
            $tile['type'] = $type;
            $group[] = $tile;
          }

          $all[$type][] = $group;
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
  protected static function url ($end, $include_root = true) {
    return ($include_root ? RPG_SERVER_ROOT : ''). SpriteSheet::DEFAULT_ICON_URL .$end;
  }
  protected static function lined_url ($end, $include_root = true) {
    return ($include_root ? RPG_SERVER_ROOT : ''). SpriteSheet::DEFAULT_LINED_URL .$end;
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