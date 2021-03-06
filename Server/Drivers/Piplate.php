<?php
namespace Theapi\Lcdproc\Server\Drivers;

use Theapi\Lcdproc\Server\Driver as Driver;
use Theapi\Lcdproc\Server\Render;

// TODO: move the outputing code to the python script at the other end of the socket
// as it has functions for chr, string, scroll, cursor etc.
class Piplate extends Driver
{

    protected $debug = 0;

    protected $disabled = false;

    // Two rows of 16(7) spaces (column 0 gets stripped later leaving 16)
    //  $outBlank[1] & $outBlank[2]
    // index starting at one because that's what lcdproc expects
    protected $outBlank = array(
        '1' => '                 ',
        '2' => '                 ',
    );
    protected $out = array();
    protected $fp;
    protected $backlightState;

    protected $lastOut; // Remember what was last sent so as not to repeat messages

    /**
     * Initialize the driver.
     */
    public function __construct($container)
    {
        parent::__construct($container);

        $this->server = '192.168.0.11'; // TODO: make pi server configurable
        $this->port = 8888;

        // Set dimensions
        $this->width = 16;
        $this->height = 2;
        $this->cellWidth = 5;
        $this->cellHeight = 5;

        // Setup the array of spaces
        $this->out = $this->outBlank;

        try {
            $this->connect();
        } catch (\Exception $e) {
            // Allow the driver to exist is an unconnected state
            // so it can be connected later with the "connect" command
            $this->container->log(LOG_ERR, $e->getMessage());
        }

    }

    /**
     * Try to make the socket connect.
     * Disable this driver if the connection fails
     *
     * The driver can be reconnected later by the server command "connect"
     *
     * @throws \Exception
     */
    public function connect()
    {

        if ($this->fp) {
            if (get_resource_type($this->fp) != 'stream') {
                $this->fp = null;
            } else {
                // already connected
                return;
            }
        }

        $this->container->log(LOG_DEBUG, 'Piplate connect()');

        // connect to the socket that the python script is listening on
        $this->fp = @stream_socket_client('tcp://' . $this->server . ':' . $this->port, $errno, $errstr, 30);

        $this->container->log(LOG_DEBUG, $this->fp . ' ' . $errstr);
        if (!$this->fp) {
            $this->disabled = true;
            throw new \Exception('Unable to connect to ' . $this->server . ':' . $this->port, $errno);
        }
        $this->disabled = false;
    }

    /**
     * Close the driver (do necessary clean-up).
     */
    public function close()
    {
        // close socket to python script

    }

    public function doesOutput() {
        return true;
    }

    public function doesInput() {
        return false; // not yet
    }

    /**
     * Clear the screen.
     */
    public function clear()
    {
        // Reset to the blank screen array
        $this->out = $this->outBlank;

        // no need to call clear on the python end as the whole
        // screen gets rendered
    }

    /**
     * Flush data on screen to the display.
     */
    public function flush()
    {
        // remove the first column
        // as it was there just because lcdproc x & y start at 1
        $line1 = substr($this->out[1], 1);
        $line2 = substr($this->out[2], 1);
        try {

            // prepend the message with "message:"
            $msg = "message:$line1\n$line2";
            if ($msg != $this->lastOut || (time() % 30 == 0) ) {
                $this->lastOut = $msg;
                $this->write($msg);
                // read just to clear the memory
                $this->read();
            }

            // Reset to the blank screen array
            $this->out = $this->outBlank;

        } catch (\Exception $e) {
            if ($e->getCode() == 0) {
                // no connection
                // try later etc...

                // for now, give up
                throw $e;
            }
        }

    }

    /**
     * Print a string on the screen at position (x,y).
     *
     * @param x        Horizontal character position (column).
     * @param y        Vertical character position (row).
     * @param string   String that gets written.
     */
    public function string($x, $y, $string)
    {
        $len = strlen($string);
        for ($i =0; $i < $len; $i++) {
            $pos = $x + $i;
            $this->chr($pos, $y, $string[$i]);
        }
    }

    /**
     * Print a character on the screen at position (x,y).
     *
     * NB: ACSII only :(
     *
     * @param x        Horizontal character position (column).
     * @param y        Vertical character position (row).
     * @param chr   String that gets written.
     */
    public function chr($x, $y, $chr)
    {
        $this->out[$y][$x] = $chr;
    }

    /**
     * Write a big number.
     * @param x        Horizontal character position (column).
     * @param num      Character to write (0 - 10 with 10 representing ':')
     */
    public function num($x, $num)
    {
        // Mmm, big numbers in 2 lines with ascii...

        // not so big for now
        if ($num == 10) {
          $chr = ':';
        } else {
          $chr = $num;
        }
        $this->chr($x, 1, $chr);
    }

    /**
     * Turn the display backlight on or off.
     *
     * @param state    New backlight status.
     */
    public function backlight($state)
    {
        // send backlight state only if needed or if it's been a while
        if ($this->backlightState !== $state || (time() % 30 == 0)) {
            $this->backlightState = $state;
            $this->write("backlight:$state");
            // read just to clear the memory
            $this->read();
        }
    }

    /**
     * Get key presses.
     */
    public function getKey()
    {

    }

    /**
     * Provide some information about this driver.
     */
    public function getInfo()
    {
        return 'Adafruit pilate driver';
    }

    public function read()
    {
        if ($this->disabled) {
            return;
        }

        if (!$this->fp) {
            return;
        }

        $line = fgets($this->fp);

        return $line;
    }

    public function write($buf)
    {
        if ($this->disabled) {
            return;
        }

        if (!$this->fp) {
            throw new \Exception('No connection to ' . $this->server . ':' . $this->port, 0);
        }

        $info = stream_get_meta_data($this->fp);
        $alive = !$info['eof'] && !$info['timed_out'];
        if (!$alive) {
            $this->container->log(LOG_ERR, 'Lost connection to ' . $this->server . ':' . $this->port);
            $this->disabled = true;
            fclose($this->fp);
            $this->fp = null;
        }

        @fwrite($this->fp, "$buf\n");

    }

    public function disconnect()
    {
        fclose($this->fp);
    }
}
