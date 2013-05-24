<?php
namespace Theapi\Lcdproc\Server;

/**
 * This file contains code that actually generates the full screen data to
 * send to the LCD.
 */

use Theapi\Lcdproc\Server;

class Render
{

  /**
   * Renders a screen. The following actions are taken in order:
   *
   * -  Clear the screen.
   * -  Set the backlight.
   * -  Set out-of-band data (output).
   * -  Render the frame contents.
   * -  Set the cursor.
   * -  Draw the heartbeat.
   * -  Show any server message.
   * -  Flush all output to screen.
   *
   * @param $screen The screen to render.
   * @param $timer  A value increased with every call.
   * @return  -1 on error, 0 on success.
   */
  public static function screen($screen, $timer) {

  }

  /**
   * Best thing to do is to remove support for frames... but anyway...
   *
   * @param unknown_type $list
   */
  public static function frame($list) {

  }

  public static function string($widget, int $left, int $top, int $right, int $bottom, int $fy) {

  }

  public static function hbar($widget, int $left, int $top, int $right, int $bottom, int $fy) {

  }

  public static function vbar($widget, int $left, int $top, int $right, int $bottom, int $fy) {

  }

  public static function title($widget, int $left, int $top, int $right, int $bottom, long $timer) {

  }

  public static function scroller($widget, int $left, int $top, int $right, int $bottom, long $timer) {

  }

  public static function num($widget, int $left, int $top, int $right, int $bottom) {

  }

  public static function msg($text, int $expire) {

  }


}