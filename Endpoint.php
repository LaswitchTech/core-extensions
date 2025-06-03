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
        $this->Public = false;

        // Set Properties
        switch($namespace){
            case "/extensions/fetchAll":
                $this->Level = 1;
                break;
        }
    }

    /**
     * Fetch all extensions
     */
    public function fetchAllAction()
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the type of extensions to fetch
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Check if the type is set
                if($type){

                    // Get the extensions listing
                    $message['data'] = $this->Helper->Extensions->get($type);
                } else {

                    // Get the extensions listing
                    $message['data'] = [
                        "modules" => $this->Helper->Extensions->get('modules'),
                        "plugins" => $this->Helper->Extensions->get('plugins'),
                        "themes" => $this->Helper->Extensions->get('themes'),
                    ];
                }
            }
        }

        // Return the message
        return $message;
    }
}
