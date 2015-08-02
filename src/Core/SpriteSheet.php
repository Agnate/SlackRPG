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

        // Determine orientation beforehand.
        $tile_count = count($tiles);
        $orientation = 'n';
        if ($tile_count == 2) {
          if ($tiles[0]['x'] != $tiles[1]['x']) $orientation = 'h';
          else $orientation = 'v';
          // else if ($tiles[0]['y'] != $tiles[1]['y']) $orientation = 'v';
        }

        foreach ($tiles as $tile) {
          $x = $col * $tile['width'];
          $y = $row * $tile['height'];
          imagecopy($image, $tile['image'], $x, $y, $tile['x'], $tile['y'], $tile['width'], $tile['height']);

          // Store new coordinates.
          $group[] = array(
            'x' => $x,
            'y' => $y,
            'orientation' => $orientation,
            'width' => $tile['width'],
            'height' => $tile['height'],
          );

          $col++;
          if ($col >= $num_cols) {
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
    // Create a list of all currently-used tiles.
    $all = SpriteSheet::all_locations();
    $all_tiles = array();
    foreach ($all as $sheet => &$sheet_set) {
      if ('/'.$sheet_set['image'] != $local_url) continue;
      foreach ($sheet_set['tiles'] as $type => &$tile_group) {
        foreach ($tile_group as &$tiles) {
          foreach ($tiles as &$tile) {
            $all_tiles[$tile['y']+1][$tile['x']+1] = TRUE;
          }
        }
      }
    }

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
    $pink = imagecolorallocatealpha($image, 223, 4, 101, 75);

    // Copy over the image we grabbed.
    $original_image = SpriteSheet::png($local_url);
    imagecopy($image, $original_image, $tile_size, $tile_size, 0, 0, $width, $height);

    // Add grid lines to make life easier.
    for ($r = 1; $r <= $num_rows; $r++) {
      $y = $r * $tile_size;

      // Add indicator to cell that it's been used.
      for ($c = 1; $c <= $num_cols; $c++) {
        if (!isset($all_tiles[$r]) || !isset($all_tiles[$r][$c]) || $all_tiles[$r][$c] != true) continue;
        $x = $c * $tile_size;
        imagefilledrectangle($image, $x, $y, $x + $tile_size, $y + $tile_size, $pink);
      }
      
      imageline($image, ($tile_size - 6), $y, $width, $y, $gray);
      // Numbers
      if ($r == $num_rows) continue;
      $row = $r-1;
      $x = ($row < 10) ? 13 : 4;
      imagettftext($image, 12, 0, $x, $y + $tile_size - 8, $gray, RPG_SERVER_ROOT.'/icons/RobotoMono-Regular.ttf', $row);
    }

    for ($c = 1; $c <= $num_cols; $c++) {
      $x = $c * $tile_size;
      imageline($image, $x, ($tile_size - 6), $x, $height, $gray);
      // Numbers
      if ($c == $num_cols) continue;
      $col = $c - 1;
      $x = ($col < 10) ? $x+12 : $x+6;
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

  protected static function all_locations () {
    $sprite_locations = array();

    $sprite_locations['terrain'] = array(
      'image' => 'terrain.png',
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
        'unknown' => array(
          array(
            array('x' => 20, 'y' => 22),
          ),
        ),
        'lava' => array(
          array(
            array('x' => 15, 'y' => 6),
          ),
          array(
            array('x' => 15, 'y' => 7),
          ),
        ),
      ),
    );

    $sprite_locations['capital'] = array(
      'image' => 'capital.png',
      'tiles' => array(
        // 'capital' => array(
        //   array(
        //     array('x' => 0, 'y' => 2),
        //     array('x' => 1, 'y' => 2),
        //     array('x' => 0, 'y' => 3),
        //     array('x' => 1, 'y' => 3),
        //   ),
        //   array(
        //     array('x' => 2, 'y' => 2),
        //     array('x' => 3, 'y' => 2),
        //     array('x' => 2, 'y' => 3),
        //     array('x' => 3, 'y' => 3),
        //   ),
        //   array(
        //     array('x' => 0, 'y' => 4),
        //     array('x' => 1, 'y' => 4),
        //     array('x' => 0, 'y' => 5),
        //     array('x' => 1, 'y' => 5),
        //   ),
        //   array(
        //     array('x' => 2, 'y' => 4),
        //     array('x' => 3, 'y' => 4),
        //     array('x' => 2, 'y' => 5),
        //     array('x' => 3, 'y' => 5),
        //   ),            
        // ),
        'estate' => array(
          array(
            array('x' => 6, 'y' => 0),
          ),
          array(
            array('x' => 6, 'y' => 1),
          ),
          array(
            array('x' => 4, 'y' => 2),
          ),
          array(
            array('x' => 6, 'y' => 4),
          ),
          array(
            array('x' => 4, 'y' => 3),
            array('x' => 5, 'y' => 3),
          ),
        ),
        'castle' => array(
          array(
            array('x' => 3, 'y' => 0),
          ),
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
        'tower' => array(
          array(
            array('x' => 0, 'y' => 0),
            array('x' => 0, 'y' => 1),
          ),
          array(
            array('x' => 7, 'y' => 0),
          ),
        ),
        'outpost' => array(
          array(
            array('x' => 11, 'y' => 4),
          ),
        ),
        'tree' => array(
          array(
            array('x' => 1, 'y' => 0),
            array('x' => 2, 'y' => 0),
            array('x' => 1, 'y' => 1),
            array('x' => 2, 'y' => 1),
          ),
        ),
        'ruin' => array(
          array(
            array('x' => 4, 'y' => 0)
          ),
          array(
            array('x' => 4, 'y' => 1)
          ),
          array(
            array('x' => 7, 'y' => 1)
          ),
        ),
        'lake' => array(
          array(
            array('x' => 7, 'y' => 3)
          ),
          array(
            array('x' => 11, 'y' => 10),
            array('x' => 12, 'y' => 10),
            array('x' => 11, 'y' => 11),
            array('x' => 12, 'y' => 11),
          ),
          array(
            array('x' => 13, 'y' => 10),
            array('x' => 13, 'y' => 11),
          ),
        ),
        'wall' => array(
          array(
            array('x' => 2, 'y' => 10),
            array('x' => 3, 'y' => 10),
            array('x' => 2, 'y' => 11),
            array('x' => 3, 'y' => 11),
          ),
          array(
            array('x' => 4, 'y' => 10),
            array('x' => 5, 'y' => 10),
            array('x' => 4, 'y' => 11),
            array('x' => 5, 'y' => 11),
          ),
        ),
      ),
    );

    $sprite_locations['citytowns'] = array(
      'image' => 'citytowns.png',
      'tiles' => array(
        'lake' => array(
          array(
            array('x' => 4, 'y' => 0),
          ),
          array(
            array('x' => 5, 'y' => 0),
          ),
        ),
        'beanstalk' => array(
          array(
            array('x' => 7, 'y' => 0),
            array('x' => 7, 'y' => 1),
          ),
        ),
        'tree' => array(
          array(
            array('x' => 3, 'y' => 1),
          ),
        ),
        'pyramid' => array(
          array(
            array('x' => 2, 'y' => 1),
          ),
        ),
        'castle' => array(
          array(
            array('x' => 8, 'y' => 0),
            array('x' => 9, 'y' => 0),
            array('x' => 8, 'y' => 1),
            array('x' => 9, 'y' => 1),
          ),
          array(
            array('x' => 10, 'y' => 0),
            array('x' => 11, 'y' => 0),
            array('x' => 10, 'y' => 1),
            array('x' => 11, 'y' => 1),
          ),
          array(
            array('x' => 12, 'y' => 0),
            array('x' => 13, 'y' => 0),
            array('x' => 12, 'y' => 1),
            array('x' => 13, 'y' => 1),
          ),
          array(
            array('x' => 14, 'y' => 0),
            array('x' => 15, 'y' => 0),
            array('x' => 14, 'y' => 1),
            array('x' => 15, 'y' => 1),
          ),
          array(
            array('x' => 8, 'y' => 2),
            array('x' => 9, 'y' => 2),
            array('x' => 8, 'y' => 3),
            array('x' => 9, 'y' => 3),
          ),
          array(
            array('x' => 10, 'y' => 2),
            array('x' => 11, 'y' => 2),
            array('x' => 10, 'y' => 3),
            array('x' => 11, 'y' => 3),
          ),
          array(
            array('x' => 11, 'y' => 6),
            array('x' => 12, 'y' => 6),
            array('x' => 11, 'y' => 7),
            array('x' => 12, 'y' => 7),
          ),
        ),
        'city' => array(
          array(
            array('x' => 0, 'y' => 11),
            array('x' => 1, 'y' => 11),
            array('x' => 0, 'y' => 12),
            array('x' => 1, 'y' => 12),
          ),
        ),
        'tower' => array(
          array(
            array('x' => 0, 'y' => 14),
            array('x' => 0, 'y' => 15),
          ),
          array(
            array('x' => 2, 'y' => 14),
            array('x' => 2, 'y' => 15),
          ),
          array(
            array('x' => 3, 'y' => 14),
            array('x' => 3, 'y' => 15),
          ),
        ),
        'outpost' => array(
          array(
            array('x' => 5, 'y' => 14),
          ),
        ),
        'ruin' => array(
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
            array('x' => 1, 'y' => 14),
            array('x' => 1, 'y' => 15),
          ),
          array(
            array('x' => 5, 'y' => 15),
          ),
        ),
        'church' => array(
          array(
            array('x' => 6, 'y' => 14),
          ),
          array(
            array('x' => 6, 'y' => 15),
          ),
          array(
            array('x' => 7, 'y' => 15),
          ),
        ),
        'estate' => array(
          array(
            array('x' => 4, 'y' => 11),
            array('x' => 5, 'y' => 11),
            array('x' => 4, 'y' => 12),
            array('x' => 5, 'y' => 12),
          ),
          array(
            array('x' => 6, 'y' => 11),
            array('x' => 7, 'y' => 11),
            array('x' => 6, 'y' => 12),
            array('x' => 7, 'y' => 12),
          ),
        ),
        'farm' => array(
          array(
            array('x' => 4, 'y' => 7),
            array('x' => 5, 'y' => 7),
          ),
          array(
            array('x' => 2, 'y' => 8),
            array('x' => 3, 'y' => 8),
          ),
        ),
        'volcano' => array(
          array(
            array('x' => 0, 'y' => 5),
            array('x' => 1, 'y' => 5),
            array('x' => 0, 'y' => 6),
            array('x' => 1, 'y' => 6),
          ),
          array(
            array('x' => 0, 'y' => 4),
            array('x' => 1, 'y' => 4),
            array('x' => 0, 'y' => 6),
            array('x' => 1, 'y' => 6),
          ),
        ),
        'mountain' => array(
          array(
            array('x' => 2, 'y' => 3),
            array('x' => 3, 'y' => 3),
            array('x' => 2, 'y' => 4),
            array('x' => 3, 'y' => 4),
          ),
          array(
            array('x' => 4, 'y' => 3),
            array('x' => 5, 'y' => 3),
            array('x' => 4, 'y' => 4),
            array('x' => 5, 'y' => 4),
          ),
          array(
            array('x' => 6, 'y' => 3),
            array('x' => 7, 'y' => 3),
            array('x' => 6, 'y' => 4),
            array('x' => 7, 'y' => 4),
          ),
        ),
        'crater' => array(
          array(
            array('x' => 0, 'y' => 3),
          ),
        ),
      ),
    );

    $sprite_locations['decoration'] = array(
      'image' => 'decoration.png',
      'tiles' => array(
        'tree' => array(
          array(
            array('x' => 0, 'y' => 6),
            array('x' => 1, 'y' => 6),
            array('x' => 0, 'y' => 7),
            array('x' => 1, 'y' => 7),
          ),
        ),
      ),
    );

    $sprite_locations['dragons'] = array(
      'image' => 'dragons.png',
      'tiles' => array(
        'tree' => array(
          array(
            array('x' => 1, 'y' => 2),
            array('x' => 2, 'y' => 2),
            array('x' => 1, 'y' => 3),
            array('x' => 2, 'y' => 3),
          ),
          array(
            array('x' => 3, 'y' => 2),
            array('x' => 4, 'y' => 2),
            array('x' => 3, 'y' => 3),
            array('x' => 4, 'y' => 3),
          ),
        ),
        'beanstalk' => array(
          array(
            array('x' => 5, 'y' => 2),
            array('x' => 5, 'y' => 3),
          ),
        ),
        'pillar' => array(
          array(
            array('x' => 5, 'y' => 4),
            array('x' => 5, 'y' => 5),
          ),
          array(
            array('x' => 2, 'y' => 6),
            array('x' => 2, 'y' => 7),
          ),
        ),
        'volcano' => array(
          array(
            array('x' => 6, 'y' => 4),
            array('x' => 7, 'y' => 4),
            array('x' => 6, 'y' => 5),
            array('x' => 7, 'y' => 5),
          ),
        ),
        'statue' => array(
          array(
            array('x' => 0, 'y' => 8),
            array('x' => 1, 'y' => 8),
            array('x' => 0, 'y' => 9),
            array('x' => 1, 'y' => 9),
          ),
          array(
            array('x' => 2, 'y' => 8),
            array('x' => 3, 'y' => 8),
            array('x' => 2, 'y' => 9),
            array('x' => 3, 'y' => 9),
          ),
        ),
        'shrine' => array(
          array(
            array('x' => 4, 'y' => 8),
          ),
        ),
      ),
    );

    $sprite_locations['gravetown'] = array(
      'image' => 'gravetown.png',
      'tiles' => array(
        'stone' => array(
          array(
            array('x' => 8, 'y' => 3),
            array('x' => 9, 'y' => 3),
            array('x' => 8, 'y' => 4),
            array('x' => 9, 'y' => 4),
          ),
        ),
        'city' => array(
          array(
            array('x' => 14, 'y' => 6),
            array('x' => 15, 'y' => 6),
            array('x' => 14, 'y' => 7),
            array('x' => 15, 'y' => 7),
          ),
          array(
            array('x' => 14, 'y' => 8),
            array('x' => 15, 'y' => 8),
            array('x' => 14, 'y' => 9),
            array('x' => 15, 'y' => 9),
          ),
          array(
            array('x' => 8, 'y' => 8),
            array('x' => 9, 'y' => 8),
            array('x' => 8, 'y' => 9),
            array('x' => 9, 'y' => 9),
          ),
          array(
            array('x' => 10, 'y' => 8),
            array('x' => 11, 'y' => 8),
            array('x' => 10, 'y' => 9),
            array('x' => 11, 'y' => 9),
          ),
          array(
            array('x' => 12, 'y' => 8),
            array('x' => 13, 'y' => 8),
            array('x' => 12, 'y' => 9),
            array('x' => 13, 'y' => 9),
          ),
        ),
        'mesa' => array(
          array(
            array('x' => 4, 'y' => 10),
          ),
          array(
            array('x' => 4, 'y' => 11),
          ),
          array(
            array('x' => 5, 'y' => 10),
          ),
          array(
            array('x' => 5, 'y' => 11),
          ),
          array(
            array('x' => 6, 'y' => 11),
          ),
          array(
            array('x' => 7, 'y' => 11),
          ),
        ),
        'graveyard' => array(
          array(
            array('x' => 3, 'y' => 9),
          ),
          array(
            array('x' => 4, 'y' => 9),
          ),
          array(
            array('x' => 5, 'y' => 9),
          ),
          array(
            array('x' => 6, 'y' => 9),
            array('x' => 6, 'y' => 10),
          ),
          array(
            array('x' => 7, 'y' => 9),
            array('x' => 7, 'y' => 10),
          ),
        ),
        'tower' => array(
          array(
            array('x' => 8, 'y' => 12),
            array('x' => 8, 'y' => 13),
          ),
          array(
            array('x' => 9, 'y' => 12),
            array('x' => 9, 'y' => 13),
          ),
          array(
            array('x' => 8, 'y' => 14),
            array('x' => 8, 'y' => 15),
          ),
          array(
            array('x' => 9, 'y' => 14),
            array('x' => 9, 'y' => 15),
          ),
        ),
      ),
    );

    $sprite_locations['graveyard'] = array(
      'image' => 'graveyard.png',
      'tiles' => array(
        'statue' => array(
          array(
            array('x' => 3, 'y' => 4),
            array('x' => 3, 'y' => 5),
          ),
          array(
            array('x' => 4, 'y' => 4),
            array('x' => 4, 'y' => 5),
          ),
          array(
            array('x' => 5, 'y' => 4),
            array('x' => 5, 'y' => 5),
          ),
          array(
            array('x' => 6, 'y' => 4),
            array('x' => 6, 'y' => 5),
          ),
          array(
            array('x' => 3, 'y' => 6),
            array('x' => 3, 'y' => 7),
          ),
          array(
            array('x' => 4, 'y' => 6),
            array('x' => 4, 'y' => 7),
          ),
          array(
            array('x' => 5, 'y' => 6),
            array('x' => 5, 'y' => 7),
          ),
          array(
            array('x' => 6, 'y' => 6),
            array('x' => 6, 'y' => 7),
          ),
        ),
        'graveyard' => array(
          array(
            array('x' => 0, 'y' => 1),
          ),
          array(
            array('x' => 1, 'y' => 4),
          ),
          array(
            array('x' => 2, 'y' => 6),
          ),
        ),
      ),
    );

    $sprite_locations['minetown'] = array(
      'image' => 'minetown.png',
      'tiles' => array(
        'mountain' => array(
          array(
            array('x' => 14, 'y' => 0),
            array('x' => 15, 'y' => 0),
            array('x' => 14, 'y' => 1),
            array('x' => 15, 'y' => 1),
          ),
        ),
        'tower' => array(
          array(
            array('x' => 4, 'y' => 3),
            array('x' => 4, 'y' => 4),
          ),
        ),
        'town' => array(
          array(
            array('x' => 11, 'y' => 8),
            array('x' => 12, 'y' => 8),
            array('x' => 11, 'y' => 9),
            array('x' => 12, 'y' => 9),
          ),
        ),
        'oasis' => array(
          array(
            array('x' => 8, 'y' => 9),
          ),
        ),
      ),
    );

    $sprite_locations['morecastle'] = array(
      'image' => 'morecastle.png',
      'tiles' => array(
        'stone' => array(
          array(
            array('x' => 10, 'y' => 5),
          ),
          array(
            array('x' => 7, 'y' => 10),
          ),
          array(
            array('x' => 7, 'y' => 11),
          ),
        ),
        'cave' => array(
          array(
            array('x' => 11, 'y' => 5),
          ),
        ),
        'graveyard' => array(
          array(
            array('x' => 7, 'y' => 9),
          ),
        ),
      ),
    );

    $sprite_locations['pillars'] = array(
      'image' => 'pillars.png',
      'tiles' => array(
        'crystal' => array(
          array(
            array('x' => 12, 'y' => 7),
          ),
          array(
            array('x' => 13, 'y' => 7),
          ),
          array(
            array('x' => 14, 'y' => 7),
          ),
          array(
            array('x' => 15, 'y' => 7),
          ),
        ),
        'fossils' => array(
          array(
            array('x' => 10, 'y' => 5),
          ),
          array(
            array('x' => 11, 'y' => 5),
          ),
        ),
      ),
    );

    $sprite_locations['statues'] = array(
      'image' => 'statues.png',
      'tiles' => array(
        'pillar' => array(
          array(
            array('x' => 10, 'y' => 2),
            array('x' => 10, 'y' => 3),
          ),
          array(
            array('x' => 3, 'y' => 3),
            array('x' => 3, 'y' => 4),
          ),
        ),
        'crystal' => array(
          array(
            array('x' => 10, 'y' => 0),
          ),
          array(
            array('x' => 11, 'y' => 0),
          ),
          array(
            array('x' => 12, 'y' => 0),
          ),
          array(
            array('x' => 13, 'y' => 0),
          ),
          array(
            array('x' => 14, 'y' => 0),
          ),
          array(
            array('x' => 15, 'y' => 0),
          ),
          array(
            array('x' => 12, 'y' => 1),
          ),
          array(
            array('x' => 13, 'y' => 1),
          ),
          array(
            array('x' => 14, 'y' => 1),
          ),
          array(
            array('x' => 15, 'y' => 1),
          ),
          array(
            array('x' => 11, 'y' => 2),
            array('x' => 11, 'y' => 3),
          ),
          array(
            array('x' => 12, 'y' => 2),
            array('x' => 12, 'y' => 3),
          ),
          array(
            array('x' => 13, 'y' => 2),
            array('x' => 13, 'y' => 3),
          ),
          array(
            array('x' => 14, 'y' => 2),
            array('x' => 14, 'y' => 3),
          ),
          array(
            array('x' => 15, 'y' => 2),
            array('x' => 15, 'y' => 3),
          ),
          array(
            array('x' => 10, 'y' => 4),
            array('x' => 10, 'y' => 5),
          ),
        ),
        'shrine' => array(
          array(
            array('x' => 3, 'y' => 0),
          ),
        ),
        'statue' => array(
          array(
            array('x' => 0, 'y' => 1),
            array('x' => 0, 'y' => 2),
          ),
          array(
            array('x' => 1, 'y' => 1),
            array('x' => 1, 'y' => 2),
          ),
          array(
            array('x' => 3, 'y' => 1),
            array('x' => 3, 'y' => 2),
          ),
          array(
            array('x' => 4, 'y' => 1),
            array('x' => 4, 'y' => 2),
          ),
          array(
            array('x' => 1, 'y' => 3),
            array('x' => 1, 'y' => 4),
          ),
          array(
            array('x' => 4, 'y' => 3),
            array('x' => 4, 'y' => 4),
          ),
          array(
            array('x' => 5, 'y' => 3),
            array('x' => 5, 'y' => 4),
          ),
          array(
            array('x' => 7, 'y' => 1),
            array('x' => 7, 'y' => 2),
          ),
          array(
            array('x' => 0, 'y' => 7),
            array('x' => 1, 'y' => 7),
            array('x' => 0, 'y' => 8),
            array('x' => 1, 'y' => 8),
          ),
          array(
            array('x' => 2, 'y' => 7),
            array('x' => 3, 'y' => 7),
            array('x' => 2, 'y' => 8),
            array('x' => 3, 'y' => 8),
          ),
          array(
            array('x' => 4, 'y' => 7),
            array('x' => 4, 'y' => 8),
          ),
          array(
            array('x' => 5, 'y' => 7),
            array('x' => 5, 'y' => 8),
          ),
        ),
      ),
    );

    $sprite_locations['bones'] = array(
      'image' => 'bones.png',
      'tiles' => array(
        'canyon' => array(
          array(
            array('x' => 5, 'y' => 2),
            array('x' => 5, 'y' => 3),
          ),
          array(
            array('x' => 6, 'y' => 2),
            array('x' => 6, 'y' => 3),
          ),
        ),
        'fossils' => array(
          array(
            array('x' => 8, 'y' => 9),
            array('x' => 9, 'y' => 9),
            array('x' => 8, 'y' => 10),
            array('x' => 9, 'y' => 10),
          ),
          array(
            array('x' => 10, 'y' => 9),
            array('x' => 10, 'y' => 10),
          ),
        ),
        'crater' => array(
          array(
            array('x' => 9, 'y' => 11),
          ),
          array(
            array('x' => 11, 'y' => 11),
          ),
          array(
            array('x' => 12, 'y' => 11),
          ),
        ),
      ),
    );

    $sprite_locations['cities'] = array(
      'image' => 'cities.png',
      'tiles' => array(
        'bridge' => array(
          array(
            array('x' => 4, 'y' => 4),
          ),
          array(
            array('x' => 5, 'y' => 4),
          ),
          array(
            array('x' => 6, 'y' => 4),
          ),
          array(
            array('x' => 7, 'y' => 4),
          ),
        ),
        'mountain' => array(
          array(
            array('x' => 0, 'y' => 1),
          ),
        ),
        'volcano' => array(
          array(
            array('x' => 2, 'y' => 1),
          ),
        ),
        'cave' => array(
          array(
            array('x' => 1, 'y' => 1),
          ),
        ),
        'crater' => array(
          array(
            array('x' => 2, 'y' => 5),
          ),
          array(
            array('x' => 3, 'y' => 5),
          ),
        ),
        'capital' => array(
          array(
            array('x' => 4, 'y' => 8),
            array('x' => 5, 'y' => 8),
            array('x' => 4, 'y' => 9),
            array('x' => 5, 'y' => 9),
          ),
        ),
        'oasis' => array(
          array(
            array('x' => 6, 'y' => 8),
            array('x' => 7, 'y' => 8),
            array('x' => 6, 'y' => 9),
            array('x' => 7, 'y' => 9),
          ),
        ),
        'forest' => array(
          array(
            array('x' => 0, 'y' => 3),
            array('x' => 0, 'y' => 3),
            array('x' => 0, 'y' => 3),
            array('x' => 0, 'y' => 3),
          ),
          array(
            array('x' => 0, 'y' => 3),
            array('x' => 2, 'y' => 3),
            array('x' => 0, 'y' => 3),
            array('x' => 3, 'y' => 3),
          ),
          array(
            array('x' => 0, 'y' => 3),
            array('x' => 0, 'y' => 3),
            array('x' => 2, 'y' => 3),
            array('x' => 0, 'y' => 3),
          ),
        ),
        'city' => array(
          array(
            array('x' => 4, 'y' => 12),
            array('x' => 5, 'y' => 12),
            array('x' => 4, 'y' => 13),
            array('x' => 5, 'y' => 13),
          ),
        ),
        'mausoleum' => array(
          array(
            array('x' => 4, 'y' => 14),
            array('x' => 5, 'y' => 14),
            array('x' => 4, 'y' => 15),
            array('x' => 5, 'y' => 15),
          ),
          array(
            array('x' => 2, 'y' => 16),
            array('x' => 3, 'y' => 16),
            array('x' => 2, 'y' => 17),
            array('x' => 3, 'y' => 17),
          ),
        ),
        'town' => array(
          array(
            array('x' => 0, 'y' => 8),
          ),
        ),
        'church' => array(
          array(
            array('x' => 0, 'y' => 9),
          ),
        ),
        'prison' => array(
          array(
            array('x' => 4, 'y' => 10),
            array('x' => 5, 'y' => 10),
            array('x' => 4, 'y' => 11),
            array('x' => 5, 'y' => 11),
          ),
        ),
      ),
    );

    $sprite_locations['flowers'] = array(
      'image' => 'flowers.png',
      'tiles' => array(
        'flowers' => array(
          array(
            array('x' => 8, 'y' => 4),
            array('x' => 9, 'y' => 4),
            array('x' => 8, 'y' => 7),
            array('x' => 9, 'y' => 7),
          ),
          array(
            array('x' => 11, 'y' => 4),
            array('x' => 12, 'y' => 4),
            array('x' => 11, 'y' => 7),
            array('x' => 12, 'y' => 7),
          ),
          array(
            array('x' => 11, 'y' => 0),
            array('x' => 12, 'y' => 0),
            array('x' => 11, 'y' => 3),
            array('x' => 12, 'y' => 3),
          ),
        ),
        'lava' => array(
          array(
            array('x' => 12, 'y' => 12),
            array('x' => 13, 'y' => 12),
            array('x' => 12, 'y' => 15),
            array('x' => 13, 'y' => 15),
          ),
        ),
      ),
    );

    $sprite_locations['flowersmore'] = array(
      'image' => 'flowersmore.png',
      'tiles' => array(
        'flowers' => array(
          array(
            array('x' => 0, 'y' => 4),
            array('x' => 1, 'y' => 4),
            array('x' => 0, 'y' => 5),
            array('x' => 1, 'y' => 5),
          ),
        ),
        'canyon' => array(
          array(
            array('x' => 4, 'y' => 6),
          ),
          array(
            array('x' => 4, 'y' => 7),
            array('x' => 5, 'y' => 7),
            array('x' => 4, 'y' => 8),
            array('x' => 5, 'y' => 8),
          ),
        ),
      ),
    );

    $sprite_locations['arch'] = array(
      'image' => 'arch.png',
      'tiles' => array(
        'cave' => array(
          array(
            array('x' => 12, 'y' => 11),
            array('x' => 13, 'y' => 11),
            array('x' => 12, 'y' => 12),
            array('x' => 13, 'y' => 12),
          ),
        ),
        'stone' => array(
          array(
            array('x' => 3, 'y' => 3),
            array('x' => 3, 'y' => 4),
          ),
        ),
      ),
    );

    return $sprite_locations;
  }

  protected static function all () {
    $tile_size = 32;

    $sprite_locations = SpriteSheet::all_locations();

    // Set width and height if they're not set.
    $all = array();
    foreach ($sprite_locations as $sprite_location => &$list) {
      // Do extra math and merge into single list.
      foreach ($list['tiles'] as $type => &$tile_group) {
        if (!isset($all[$type])) $all[$type] = array();

        // Load up the image sprite that contains these tiles.
        $list['loaded_image'] = SpriteSheet::png('/'.$list['image']);

        foreach ($tile_group as &$tiles) {
          $group = array();
          
          foreach ($tiles as &$tile) {
            $tile['col'] = $tile['x'];
            $tile['row'] = $tile['y'];
            $tile['x'] = $tile['x'] * $tile_size;
            $tile['y'] = $tile['y'] * $tile_size;
            if (!isset($tile['width'])) $tile['width'] = $tile_size;
            if (!isset($tile['height'])) $tile['height'] = $tile_size;
            $tile['image'] = $list['loaded_image'];
            $tile['origin'] = $sprite_location;
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