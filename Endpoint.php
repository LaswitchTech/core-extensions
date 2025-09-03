<?php

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
                $this->Public = !$this->Config->get('application', 'installed');
                $this->Level = 2;
                break;
            case "/extensions/meta":
            case "/extensions/publish":
            case "/extensions/unpublish":
            case "/extensions/update":
            case "/extensions/import":
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
    public function fetchAllAction(): array
    {
        // Set the default message
        $message = ["status" => 200, "message" => "OK", "data" => []];

        // Check if the status is still OK
        if($message['status'] == 200){

            // Check the request method
            if($this->Request->getMethod() == "GET"){

                // Get the extensions listing
                $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                // Sort the extensions by type
                ksort($extensions);

                // Loop through the extensions
                foreach($extensions as $type => $list){

                    // Sort the extensions by base
                    ksort($list);

                    // Loop through the extensions
                    foreach($list as $base => $extension){

                        // Set the extension in the message data
                        $message['data'][$type][$base] = $extension;
                    }
                }
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Update the meta information
     */
    public function metaAction(): array
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

                    // Load the meta information
                    $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                    // Check if the type and base exist in the extensions
                    if(array_key_exists($type, $extensions) && array_key_exists($base, $extensions[$type])){

                        // Select the extension
                        $extension = $extensions[$type][$base];

                        // Check if git is enabled
                        if($extension['git']){

                            // Replace the meta information with the data provided
                            foreach($meta as $key => $value) {
                                if(array_key_exists($key, $extension)) {
                                    $extension[$key] = $value;
                                }
                            }

                            // Set the paths
                            $infoPath = $extension['path'] . DIRECTORY_SEPARATOR . 'info.cfg';
                            $gitPath = $extension['path'] . DIRECTORY_SEPARATOR . '.git';

                            // Unset the meta information that is not allowed
                            foreach($extension as $key => $value) {
                                if(!in_array($key, ['name', 'type', 'base', 'author', 'email', 'date', 'version', 'tags', 'description', 'repository', 'download', 'tracker', 'support', 'picture'])) {
                                    unset($extension[$key]);
                                }
                            }

                            // Convert the extension to JSON
                            $json = json_encode($extension, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                            // Update the meta information
                            $message["data"]["status"] = is_dir($gitPath) ? file_put_contents($infoPath, $json) !== false : false;
                        } else {

                            // Set the error message
                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Git is not enabled for this extension."];
                        }
                    } else {

                        // Set the error message
                        $message = ["status" => 404, "message" => "Not Found", "data" => "Extension not found."];
                    }
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
    public function publishAction(): array
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

                // Retrieve the feed of the extensions to be published in
                $feed = $this->Request->getParams('REQUEST', 'feed') ?? null;

                // Check if the required data is set
                if($type && $base && $feed){

                    // Load the meta information
                    $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                    // Check if the type and base exist in the extensions
                    if(array_key_exists($type, $extensions) && array_key_exists($base, $extensions[$type])){

                        // Select the extension
                        $extension = $extensions[$type][$base];

                        // Check if published is enabled
                        if(!$extension['published']){

                            // Check if git is enabled
                            if($extension['git']){

                                // Check if the feed is the local feed
                                if($feed == 'local'){

                                    // Retrieve the application feed listing
                                    $feedListing = $this->Config->get('extensions');

                                    // Parse the repository URL
                                    $url = $this->Helper->Core->getRepo($extension['repository'])['url'];

                                    // Check if the URL is valid
                                    if($url){

                                        // Set the extension in the feed listing
                                        $feedListing[$type][$base] = ['url' => $url];

                                        // Check if the token is set
                                        if(array_key_exists('token', $extension) && $extension['token']){

                                            // Set the token in the feed listing
                                            $feedListing[$type][$base]['token'] = $extension['token'];
                                        }

                                        // Save the feed listing
                                        $message["data"]["status"] = $this->Config->set('extensions', $type, $feedListing[$type]);
                                    } else {

                                        // Set the error message
                                        $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid repository URL."];
                                    }
                                } else {

                                    // Check if the feed exist in the extensions
                                    if(array_key_exists('modules', $extensions) && array_key_exists($feed, $extensions['modules'])){

                                        // Select the feed
                                        $feed = $extensions['modules'][$feed];

                                        // Check if git is enabled
                                        if($feed['git']){

                                            // Retrieve the application feed listing
                                            $feedListing = json_decode(file_get_contents($feed['path'] . DIRECTORY_SEPARATOR . "listing.cfg") ?? "[]", true);

                                            // Parse the repository URL
                                            $url = $this->Helper->Core->getRepo($extension['repository'])['url'];

                                            // Check if the URL is valid
                                            if($url){

                                                // Set the extension in the feed listing
                                                $feedListing[$type][$base] = ['url' => $url];

                                                // Check if the token is set
                                                if(array_key_exists('token', $extension) && $extension['token']){

                                                    // Set the token in the feed listing
                                                    $feedListing[$type][$base]['token'] = $extension['token'];
                                                }

                                                // Convert the feed listing to JSON
                                                $json = json_encode($feedListing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                                                // Save the feed listing to the file
                                                $message["data"]["status"] = file_put_contents($feed['path'] . DIRECTORY_SEPARATOR . "listing.cfg", $json) !== false;
                                            } else {

                                                // Set the error message
                                                $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid repository URL."];
                                            }
                                        } else {

                                            // Set the error message
                                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Git is not enabled for this feed."];
                                        }
                                    } else {

                                        // Set the error message
                                        $message = ["status" => 404, "message" => "Not Found", "data" => "Feed not found."];
                                    }
                                }
                            } else {

                                // Set the error message
                                $message = ["status" => 400, "message" => "Bad Request", "data" => "Git is not enabled for this extension."];
                            }
                        } else {

                            // Set the error message
                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Extension is already published."];
                        }
                    } else {

                        // Set the error message
                        $message = ["status" => 404, "message" => "Not Found", "data" => "Extension not found."];
                    }
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Type, base and feed parameters are required."];
                }
            } else {

                // Set the error message
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "This endpoint only accepts POST requests."];
            }
        }

        // Return the message
        return $message;
    }

    /**
     * Unpublish an extension
     */
    public function unpublishAction(): array
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

                    // Load the meta information
                    $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                    // Check if the type and base exist in the extensions
                    if(array_key_exists($type, $extensions) && array_key_exists($base, $extensions[$type])){

                        // Select the extension
                        $extension = $extensions[$type][$base];

                        // Check if published is enabled
                        if($extension['published']){

                            // Check if git is enabled
                            if($extension['git']){

                                // Retrieve the source from which the extension was published
                                $source = trim(str_replace($this->Config->root(),'',$extension['source']), DIRECTORY_SEPARATOR);

                                // Retrieve the first part of the source
                                $sourcePath = explode(DIRECTORY_SEPARATOR, $source)[0];

                                // Identify the listing path
                                switch($sourcePath){
                                    case 'config':
                                        $gitPath = $this->Config->root() . DIRECTORY_SEPARATOR . '.git';
                                        $headPath = $gitPath . DIRECTORY_SEPARATOR . 'HEAD';
                                        $feedGit = is_dir($gitPath) && file_exists($headPath);
                                        break;
                                    case 'lib':
                                        $gitPath = dirname($extension['source']) . DIRECTORY_SEPARATOR . '.git';
                                        $headPath = $gitPath . DIRECTORY_SEPARATOR . 'HEAD';
                                        $feedGit = is_dir($gitPath) && file_exists($headPath);
                                        break;
                                    default:
                                        $feedGit = false;
                                        break;
                                }

                                // Check if the feed is in development
                                if($feedGit){

                                    // Retrieve the application feed listing
                                    $feedListing = json_decode(file_get_contents($extension['source']) ?? "[]", true);

                                    // Parse the repository URL
                                    $url = $this->Helper->Core->getRepo($extension['repository'])['url'];

                                    // Check if the URL is valid
                                    if($url){

                                        // Check if the feed exist in the extensions
                                        if(array_key_exists($type, $feedListing) && array_key_exists($base, $feedListing[$type])){

                                            // Unset the extension from the feed listing
                                            unset($feedListing[$type][$base]);

                                            // Convert the feed listing to JSON
                                            $json = json_encode($feedListing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

                                            // Save the feed listing to the file
                                            $message["data"]["status"] = file_put_contents($extension['source'], $json) !== false;
                                        } else {

                                            // Set the error message
                                            $message = ["status" => 404, "message" => "Not Found", "data" => "Could not find the extension in the feed listing."];
                                        }
                                    } else {

                                        // Set the error message
                                        $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid repository URL."];
                                    }
                                } else {

                                    // Set the error message
                                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Git is not enabled for this feed."];
                                }
                            } else {

                                // Set the error message
                                $message = ["status" => 400, "message" => "Bad Request", "data" => "Git is not enabled for this extension."];
                            }
                        } else {

                            // Set the error message
                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Extension is not published."];
                        }
                    } else {

                        // Set the error message
                        $message = ["status" => 404, "message" => "Not Found", "data" => "Extension not found."];
                    }
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
    public function installAction(): array
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

                    // Load the meta information
                    $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                    // Check if the type and base exist in the extensions
                    if(array_key_exists($type, $extensions) && array_key_exists($base, $extensions[$type])){

                        // Select the extension
                        $extension = $extensions[$type][$base];

                        // Check if the extension is already installed
                        if(!$extension['installed']){

                            // Set a temporary path
                            $tmpPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'tmp';

                            // Set a path for the archive
                            $archivePath = $tmpPath . DIRECTORY_SEPARATOR . $extension['base'] . '.zip';

                            // Download the extension archive
                            if($this->Helper->Core->download($extension['download'], $archivePath, $extension['token'] ?? null)){

                                // Unpack the archive to the extension path
                                if($this->Helper->Core->unpack($archivePath, $extension['path'])){

                                    // Unset the archive file
                                    if(file_exists($archivePath)){
                                        unlink($archivePath);
                                    }

                                    // Import the extension's database schema
                                    if($this->Model->Core->import($extension['path'] . DIRECTORY_SEPARATOR . "Install")){

                                        // Set the status
                                        $message["data"]["status"] = true;
                                    } else {

                                        // Set the error message
                                        $message = ["status" => 400, "message" => "Bad Request", "data" => "Failed to create the extension's database."];
                                    }
                                } else {

                                    // Set the error message
                                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Could not unpack the extension archive."];
                                }
                            } else {

                                // Set the error message
                                $message = ["status" => 400, "message" => "Bad Request", "data" => "Could not download the extension archive."];
                            }
                        } else {

                            // Set the error message
                            $message = ["status" => 200, "message" => "OK", "data" => "Extension is already installed."];
                        }
                    } else {

                        // Set the error message
                        $message = ["status" => 404, "message" => "Not Found", "data" => "Extension not found."];
                    }
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
    public function uninstallAction(): array
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

                    // Load the meta information
                    $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                    // Check if the type and base exist in the extensions
                    if(array_key_exists($type, $extensions) && array_key_exists($base, $extensions[$type])){

                        // Select the extension
                        $extension = $extensions[$type][$base];

                        // Check if the extension is already installed
                        if($extension['installed']){

                            // Delete the extension path
                            if($this->Helper->Core->delete($extension['path'])){

                                // Set the status
                                $message["data"]["status"] = true;
                            } else {

                                // Set the error message
                                $message = ["status" => 400, "message" => "Bad Request", "data" => "Failed to uninstall the extension."];
                            }
                        } else {

                            // Set the error message
                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Extension is not installed."];
                        }
                    } else {

                        // Set the error message
                        $message = ["status" => 404, "message" => "Not Found", "data" => "Extension not found."];
                    }
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
    public function updateAction(): array
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

                    // Load the meta information
                    $extensions = $this->Helper->Core->loadExtensionsMeta(true);

                    // Check if the type and base exist in the extensions
                    if(array_key_exists($type, $extensions) && array_key_exists($base, $extensions[$type])){

                        // Select the extension
                        $extension = $extensions[$type][$base];

                        // Check if the extension is already installed
                        if($extension['installed']){

                            // Set a temporary path
                            $tmpPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'tmp';

                            // Set a path for the archive
                            $archivePath = $tmpPath . DIRECTORY_SEPARATOR . $extension['base'] . '.zip';

                            // Download the extension archive
                            if($this->Helper->Core->download($extension['download'], $archivePath, $extension['token'] ?? null)){

                                // Unpack the archive to the extension path
                                if($this->Helper->Core->unpack($archivePath, $extension['path'])){

                                    // Unset the archive file
                                    if(file_exists($archivePath)){
                                        unlink($archivePath);
                                    }

                                    // Import the extension's database schema
                                    if($this->Model->Core->import($extension['path'] . DIRECTORY_SEPARATOR . "Install", file_get_contents($extension['path'] . DIRECTORY_SEPARATOR . 'VERSION'))){

                                        // Set the status
                                        $message["data"]["status"] = true;
                                    } else {

                                        // Set the error message
                                        $message = ["status" => 400, "message" => "Bad Request", "data" => "Failed to create the extension's database."];
                                    }
                                } else {

                                    // Set the error message
                                    $message = ["status" => 400, "message" => "Bad Request", "data" => "Could not unpack the extension archive."];
                                }
                            } else {

                                // Set the error message
                                $message = ["status" => 400, "message" => "Bad Request", "data" => "Could not download the extension archive."];
                            }
                        } else {

                            // Set the error message
                            $message = ["status" => 400, "message" => "Bad Request", "data" => "Extension is not installed."];
                        }
                    } else {

                        // Set the error message
                        $message = ["status" => 404, "message" => "Not Found", "data" => "Extension not found."];
                    }
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
     * Import an extension
     */
    public function importAction(): array
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

                // Retrieve the url of extensions
                $url = $this->Request->getParams('POST', 'url') ?? null;

                // Retrieve the token of the extensions
                $token = $this->Request->getParams('POST', 'token') ?? null;
                $token = empty($token) ? null : $token;

                // Check if the url is set
                if($url){

                    // Parse the repository URL
                    $url = $this->Helper->Core->getRepo($url)['url'];

                    // Check if the URL is valid
                    if($url){

                        // Load the extension info
                        $extension = $this->Helper->Core->retrieve($url, $token ?? null);

                        // Set the type and base
                        $type = $extension['type'] ?? 'modules';
                        $base = $extension['base'] ?? null;

                        // Retrieve the application feed listing
                        $feedListing = $this->Config->get('extensions');

                        // Set the extension in the feed listing
                        $feedListing[$type][$base] = ['url' => $url];

                        // Check if the token is set
                        if(array_key_exists('token', $extension) && $extension['token']){

                            // Set the token in the feed listing
                            $feedListing[$type][$base]['token'] = $extension['token'];
                        }

                        // Save the feed listing
                        $message["data"]["status"] = $this->Config->set('extensions', $type, $feedListing[$type]);
                    } else {

                        // Set the error message
                        $message = ["status" => 400, "message" => "Bad Request", "data" => "Invalid repository URL."];
                    }
                } else {

                    // Set the error message
                    $message = ["status" => 400, "message" => "Bad Request", "data" => "URL parameters is required."];
                }
            } else {

                // Set the error message
                $message = ["status" => 405, "message" => "Method Not Allowed", "data" => "This endpoint only accepts POST requests."];
            }
        }

        // Return the message
        return $message;
    }
}
