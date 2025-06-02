<?php

/**
 * Core Framework - ExtensionsEndpoint
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Endpoint;

class ExtensionsEndpoint extends Endpoint {

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call Parent Constructor
        parent::__construct();

        // Retrieve the namespace
        $namespace = $this->Request->getNamespace();

        // Set Global access
        $this->Public = true;

        // Set Properties
        switch($namespace){
            case "/extensions/status":
                $this->Level = 1;
                break;
            case "/extensions/on":
            case "/extensions/off":
                $this->Public = false;
                $this->Level = 1;
                break;
        }
    }
}
