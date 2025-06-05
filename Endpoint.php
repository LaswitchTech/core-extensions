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
            case "/extensions/install":
                $this->Level = 2;
                break;
            case "/extensions/meta":
            case "/extensions/publish":
            case "/extensions/unpublish":
            case "/extensions/update":
                $this->Level = 3;
                break;
            case "/extensions/uninstall":
                $this->Level = 4;
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

                // Get the extensions listing
                $message['data'] = $this->Helper->Core->loadExtensionsMeta(true);
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Update the meta information
     */
    public function metaAction()
    {
        // Import Global Variables
        global $CSRF;

        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check the request method
        if($this->Request->getMethod() == "POST"){
            $message["data"]["CSRF"] = [
                "token" => $CSRF->token(),
                "key" => $CSRF->key()
            ];
        }

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "POST"){

                // Retrieve the type of extensions to update
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Retrieve the base of the extensions to update
                $base = $this->Request->getParams('GET', 'base') ?? null;

                // Check if the type is set
                if($type && $base){

                    // Retrieve the meta information from the request
                    $meta = $this->Request->getParams('POST', 'meta') ?? null;

                    // Update the meta information
                    $message["data"]["status"] = $this->Helper->Extensions->meta($type, $base, $meta);
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type and base parameters are required."];
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Publish an extension
     */
    public function publishAction()
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the type of extensions to update
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Retrieve the base of the extensions to update
                $base = $this->Request->getParams('GET', 'base') ?? null;

                // Check if the type is set
                if($type && $base){

                    // Update the meta information
                    $message["data"]["status"] = $this->Helper->Extensions->publish($type, $base);
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type and base parameters are required."];
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Unpublish an extension
     */
    public function unpublishAction()
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the type of extensions to update
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Retrieve the base of the extensions to update
                $base = $this->Request->getParams('GET', 'base') ?? null;

                // Check if the type is set
                if($type && $base){

                    // Update the meta information
                    $message["data"]["status"] = $this->Helper->Extensions->unpublish($type, $base);
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type and base parameters are required."];
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Install an extension
     */
    public function installAction()
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the type of extensions to update
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Retrieve the base of the extensions to update
                $base = $this->Request->getParams('GET', 'base') ?? null;

                // Check if the type is set
                if($type && $base){

                    // Update the meta information
                    $message["data"]["status"] = $this->Helper->Extensions->install($type, $base);
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type and base parameters are required."];
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Uninstall an extension
     */
    public function uninstallAction()
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the type of extensions to update
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Retrieve the base of the extensions to update
                $base = $this->Request->getParams('GET', 'base') ?? null;

                // Check if the type is set
                if($type && $base){

                    // Update the meta information
                    $message["data"]["status"] = $this->Helper->Extensions->uninstall($type, $base);
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type and base parameters are required."];
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Update an extension
     */
    public function updateAction()
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Retrieve the type of extensions to update
                $type = $this->Request->getParams('GET', 'type') ?? null;

                // Retrieve the base of the extensions to update
                $base = $this->Request->getParams('GET', 'base') ?? null;

                // Check if the type is set
                if($type && $base){

                    // Update the meta information
                    $message["data"]["status"] = $this->Helper->Extensions->install($type, $base);
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type and base parameters are required."];
                }
            }
        }

        // Return the message
        return $message;
    }
}
