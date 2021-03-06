<?php
namespace Theapi\Lcdproc\Server\Commands;

use Theapi\Lcdproc\Server\Widget;
use Theapi\Lcdproc\Server\Server;
use Theapi\Lcdproc\Server\Exception\ClientException;

/**
 * Implements handlers for client commands concerning widgets.
 *
 * This contains definitions for all the functions which clients can run.
 *
 * The client's available function set is defined here, as is the syntax
 * for each command.
 */

/* This file is part of phpLCDd, the php lcdproc server.
 *
 * This file is released under the GNU General Public License.
 * Refer to the COPYING file distributed with this package.
 *
 */

class WidgetCommands
{

    protected $client;

    public function __construct($client)
    {
        $this->client = $client;
        $this->container = $this->client->container;
    }


    /**
     * Adds a widget to a screen, but doesn't give it a value
     *
     * Usage: widget_add <screenid> <widgetid> <widgettype> [-in <id>]
     */
    public function add($args)
    {
        if (!$this->client->isActive()) {
            return 1;
        }

        $countArgs = count($args);
        if ( ($countArgs < 3) || ($countArgs > 5) ) {
            throw new ClientException(
                $this->client,
                'Usage: widget_add <screenid> <widgetid> <widgettype> [-in <id>]'
            );
        }

        $sid = $args[0];
        $wid = $args[1];
        $s = $this->client->findScreen($sid);
        if ($s == null) {
            throw new ClientException('Invalid screen id');
        }

        // Find widget type
        $wtype = Widget::typeNameToType($args[2]);
        if ($wtype == Widget::WID_NONE) {
            throw new ClientException('Invalid widget type');
        }

        // Check for additional flags...
        if ($countArgs > 3) {
            // ignore leading '-' in options: we allow both forms
            if (trim($args[3], ' -') == 'in') {
                if (empty($args[4])) {
                    throw new ClientException('Specify a frame to place widget in');
                }

                // Now we replace $s with the framescreen.
                // This way it will not be plaed in the normal screen
                // but in the framescreen.
                $frame = $s->findWidget($args[4]);
                if (empty($frame)) {
                    throw new ClientException('Error finding frame');
                }
                $s = $frame->frameScreen;
            }
        }

        // Create the widget
        $w = new Widget($wid, $wtype, $s);
        if ($w == null) {
            throw new ClientException('Error adding widget');
        }

        // Add the widget to the screen
        $err = $s->addWidget($w);
        if ($err == 0) {
            $this->client->sendString("success\n");
        } else {
            throw new ClientException('Error adding widget');
        }

        return 0;
    }


    /**
     * Removes a widget from a screen, and forgets about it
     *
     * Usage: widget_del <screenid> <widgetid>
     */
    public function del($args)
    {
        if (!$this->client->isActive()) {
            return 1;
        }

        if (count($args) != 2) {
            throw new ClientException('Usage: widget_del <screenid> <widgetid>');
        }

        $sid = $args[0];
        $wid = $args[1];
        $s = $this->client->findScreen($sid);
        if ($s == null) {
            throw new ClientException('Invalid screen id');
        }

        $w = $s->findWidget($wid);
        if ($w == null) {
            throw new ClientException('Invalid widget id');
        }

        $err = $s->removeWidget($w);
        if ($err == 0) {
            $this->client->sendString("success\n");
        } else {
            throw new ClientException('Error removing widget');
        }

        return 0;
    }

    /**
     * Configures information about a widget, such as its size, shape,
     * contents, position, speed, etc.
     *
     * widget_set <screenid> <widgetid> <widget-SPECIFIC-data>
     *
     */
    public function set($args)
    {
        if (!$this->client->isActive()) {
            return 1;
        }

        // If there weren't enough parameters...
        // We can't test for too many, since each widget may have a
        // different number - plus, if the argument count is wrong, what ELSE
        // could be wrong...?
        if (count($args) < 3) {
            throw new ClientException('Usage: widget_set <screenid> <widgetid> <widget-SPECIFIC-data>');
        }

        // Find screen
        $sid = $args[0];
        $s = $this->client->findScreen($sid);
        if ($s == null) {
            throw new ClientException('Unknown screen id:' . $sid);
        }

        // Find widget
        $wid = $args[1];
        $w = $s->findWidget($wid);
        if ($w == null) {
            throw new ClientException('Unknown widget id:' . $wid);
        }

        switch ($w->type) {
            case Widget::WID_STRING:
                // String takes "x y text"
                if (!isset($args[4])) {
                    throw new ClientException('Wrong number of arguments');
                }
                if (!is_numeric($args[2]) || !is_numeric($args[3])) {
                    throw new ClientException('Invalid coordinates');
                }

                $w->x = (int) $args[2];
                $w->y = (int) $args[3];
                $w->text = $args[4];
                $this->client->sendString("success\n");
                break;
            case Widget::WID_HBAR:
                // Hbar takes "x y length"
                if (!isset($args[4])) {
                    throw new ClientException('Wrong number of arguments');
                }
                if (!is_numeric($args[2]) || !is_numeric($args[3])) {
                    throw new ClientException('Invalid coordinates');
                }

                $w->x = (int) $args[2];
                $w->y = (int) $args[3];
                // This is the length in pixels
                $w->length = (int) $args[4];

                $this->client->sendString("success\n");
                break;
            case Widget::WID_VBAR:
                // Vbar takes "x y length"
                if (!isset($args[4])) {
                    throw new ClientException('Wrong number of arguments');
                }
                if (!is_numeric($args[2]) || !is_numeric($args[3])) {
                    throw new ClientException('Invalid coordinates');
                }

                $w->x = (int) $args[2];
                $w->y = (int) $args[3];
                // This is the length in pixels
                $w->length = (int) $args[4];

                $this->client->sendString("success\n");
                break;
            case Widget::WID_ICON:
                // Icon takes "x y icon"
                if (!isset($args[4])) {
                    throw new ClientException('Wrong number of arguments');
                }
                if (!is_numeric($args[2]) || !is_numeric($args[3])) {
                    throw new ClientException('Invalid coordinates');
                }

                $w->x = (int) $args[2];
                $w->y = (int) $args[3];
                $icon = Widget::iconNameToIcon($args[4]);
                if (!$icon) {
                    throw new ClientException('Invalid icon name');
                }
                $w->length = $icon;

                $this->client->sendString("success\n");
                break;
            case Widget::WID_TITLE:
                // title takes "text"
                if (!isset($args[2])) {
                    throw new ClientException('Wrong number of arguments');
                }

                $w->text = $args[2];
                // Set width too
                $w->width = $this->client->container->drivers->displayProps->width;

                $this->client->sendString("success\n");
                break;
            case Widget::WID_SCROLLER:
                // Scroller takes "left top right bottom direction speed text"
                if (!isset($args[7])) {
                    throw new ClientException('Wrong number of arguments');
                }

                if (!isset($args[8])) {
                    $args[8] = '';
                }

                if (!is_numeric($args[2])
                    || !is_numeric($args[3])
                    || !is_numeric($args[4])
                    || !is_numeric($args[5])) {
                    throw new ClientException('Invalid coordinates');
                }

                // Direction must be m, v or h
                if ($args[6] != 'm' && $args[6] != 'v' && $args[6] != 'h') {
                    throw new ClientException('Invalid direction');
                }

                $w->left = (int) $args[2];
                $w->top = (int) $args[3];
                $w->right = (int) $args[4];
                $w->bottom = (int) $args[5];
                $w->length = (int) $args[6];
                $w->speed = (int) $args[7];
                $w->text = $args[8];

                $this->client->sendString("success\n");
                break;
            case Widget::WID_FRAME:
                // Frame takes "left top right bottom wid hgt direction speed"
                if (!isset($args[9])) {
                    throw new ClientException('Wrong number of arguments');
                }

                if (!is_numeric($args[2])
                    || !is_numeric($args[3])
                    || !is_numeric($args[4])
                    || !is_numeric($args[5])
                    || !is_numeric($args[6])
                    || !is_numeric($args[7])) {
                    throw new ClientException('Invalid coordinates');
                }

                // Direction must be v or h
                if ($args[8] != 'h' && $args[8] != 'v') {
                    throw new ClientException('Invalid direction');
                }

                $w->left = (int) $args[2];
                $w->top = (int) $args[3];
                $w->right = (int) $args[4];
                $w->bottom = (int) $args[5];
                $w->width = (int) $args[6];
                $w->height = (int) $args[7];
                $w->length = $args[8];
                $w->speed = $args[9];
                $this->client->sendString("success\n");

                break;
            case Widget::WID_NUM:
                // Num takes "x num"
                if (!isset($args[3])) {
                    throw new ClientException('Wrong number of arguments');
                }
                if (!is_numeric($args[2]) || !is_numeric($args[2])) {
                    throw new ClientException('Invalid coordinates');
                }

                $w->x = (int) $args[2];
                $w->y = (int) $args[3];

                $this->client->sendString("success\n");
                break;
            case Widget::WID_NONE:
                throw new ClientException('Widget has no type');
                break;
        }

        return 0;
    }
}
