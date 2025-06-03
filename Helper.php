<?php

/**
 * Core Framework - ExtensionsHelper
 *
 * @license    MIT (https://mit-license.org/)
 * @author     Louis Ouellet <louis@laswitchtech.com>
 */

// Import additionnal class into the global namespace
use \LaswitchTech\Core\Abstracts\Helper;

class ExtensionsHelper extends Helper {

    protected $pluginDir;
    protected $listing = [];
    protected $extensions = [];

    /**
     * Constructor
     */
    public function __construct()
    {

        // Call parent constructor
        parent::__construct();

        // Set the plugin directory
        $this->pluginDir = dirname(__FILE__);
    }

    /**
     * Set the default values for an extension.
     *
     * @param string $type The type of extension (e.g., 'module', 'plugin', 'theme').
     * @param string $base The base name of the extension.
     * @return array
     */
    protected function defaults(string $type, string $base): array
    {
        // Set Default info;
        return [
            "name"        => ucwords(str_replace('-', ' ', $base)),
            "type"        => $type,
            "base"        => $base,
            "author"      => null,
            "email"       => null,
            "date"        => date('Y-m-d'),
            "current"     => "v0.0.0",
            "version"     => "v0.0.0",
            "tags"        => null,
            "description" => "An extension for the Core Framework.",
            "repository"  => null,
            "branch"      => "main",
            "token"       => null,
            "download"    => null,
            "tracker"     => null,
            "support"     => null,
            "picture"     => null,
        ];
    }

    /**
     * Fetch a JSON file (public or private) and return it as an associative array.
     *
     * @param string      $url    Full URL to the file or GitHub API endpoint.
     * @param string|null $token  Personal‑access token (or fine‑grained token).
     * @return array              Decoded JSON (or an empty array if the URL responds with HTTP 404).
     * @throws RuntimeException   On network errors, other HTTP errors, or JSON decode errors.
     */
    protected function retrieve(string $url, ?string $token = null): array
    {
        $ch       = curl_init($url);
        $headers  = ['User-Agent: Core-Framework'];

        // Ask GitHub’s REST API for the raw file
        if (preg_match('#^https?://api\.github\.com/#', $url)) {
            $headers[] = 'Accept: application/vnd.github.raw';
        }

        // Optional authentication
        if ($token !== null) {
            $headers[] = "Authorization: Bearer {$token}";
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $body = curl_exec($ch);

        if ($body === false) {
            throw new RuntimeException('cURL error: ' . curl_error($ch));
        }

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // ── HTTP error handling ───────────────────────────────────────
        if ($status === 404) {
            return [];                    // “not found” → empty result
        }

        if ($status >= 400) {             // any other 4xx/5xx → exception
            throw new RuntimeException("HTTP $status returned for $url");
        }

        // ── Decode JSON ───────────────────────────────────────────────
        $data = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            // If we accidentally received GitHub’s wrapper JSON, unwrap it
            if (isset($data['encoding'], $data['content']) && $data['encoding'] === 'base64') {
                $decoded = json_decode(base64_decode($data['content'], true), true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new RuntimeException(
                        'JSON decode error (inner content): ' . json_last_error_msg()
                    );
                }
                return $decoded;
            }
            return $data;                 // regular JSON
        }

        throw new RuntimeException('JSON decode error: ' . json_last_error_msg());
    }

    /**
     * Download a file
     *
     * @param string $url
     * @param string $destination
     * @return bool
     */
    protected function download(string $url, string $destination, $token = null): bool
    {
        // Check if the URL is valid
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        // Retrieve the name
        $name = $this->Config->get('installer','name');

        // Check if the destination directory exists
        if(!is_dir(dirname($destination))){
            mkdir(dirname($destination), 0755, true);
        }

        // Check if the destination file exists
        if(file_exists($destination)){
            unlink($destination);
        }

        // Initialize curl
        $cURL = curl_init($url);

        // Set Headers
        $headers = [
            'User-Agent: ' . $name,
            'Accept: application/octet-stream',
        ];
        if (!is_null($token) && !empty($token)) {
            $headers[] = 'Authorization: token ' . $token;
        }

        // Set options for the cURL request
        $cURLOptions = [
            // Provide metadata
            CURLOPT_USERAGENT => $name,
            // Insert Headers
            CURLOPT_HEADER => 0,
            CURLOPT_HTTPHEADER => $headers,
            // Return the transfer as a string
            CURLOPT_RETURNTRANSFER => true,
            // Handle Redirections
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            // Handle Connection Timeout
            CURLOPT_TIMEOUT => 30,
            // Disable SSL Verification
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ];

        // Set the cURL options
        curl_setopt_array($cURL, $cURLOptions);

        // Execute the request
        $stream = curl_exec($cURL);
        $status = curl_getinfo($cURL, CURLINFO_HTTP_CODE);
        $error = curl_error($cURL);

        // Close cURL session
        curl_close($cURL);

        // Check if the request was successful
        if ($status !== 200) {
            return false;
        }

        // Create the file using file_put_contents
        $result = file_put_contents($destination, $stream);
        if ($result === false) {
            return false;
        }

        return true;
    }

    /**
     * Unpack (extract) a zip archive to a given location.
     *
     * @param string $source Path to the ZIP file.
     * @param string $destination Directory where files should be extracted.
     * @return bool true on success, false on failure
     */
    protected function unpack(string $source, string $destination): bool
    {
        // Check if the archive file exists
        if (!file_exists($source) || !is_file($source)) {
            return false;
        }

        // Attempt to create the destination directory if it doesn't exist
        if (!is_dir($destination) && !mkdir($destination, 0755, true) && !is_dir($destination)) {
            return false;
        }

        // Initialize a new ZipArchive instance
        $zip = new ZipArchive();

        // Try opening the ZIP file
        if ($zip->open($source) !== true) {
            return false;
        }

        // Extract the contents to the specified destination
        if (!$zip->extractTo($destination)) {
            $zip->close();
            return false;
        }

        // Close the ZIP
        $zip->close();

        // Done
        return true;
    }

    /**
     * Recursively delete a directory (including its contents).
     *
     * @param string $directory Path to the directory you want to remove
     * @return bool true on success, false on failure
     */
    protected function delete(string $directory): bool
    {
        // If it doesn't exist, treat it as an error or success depending on your preference
        if (!file_exists($directory)) {
            // Option 1: Treat as an error
            return false;
        }

        // If it's a file or symlink, just unlink it
        if (!is_dir($directory)) {
            if (!@unlink($directory)) {
                return false;
            }
            return true;
        }

        // Otherwise, recursively remove contents
        $items = scandir($directory);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            // Skip pointers
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            // Recursively call delete on each item
            if (!$this->delete($path)) {
                // If any item fails to be deleted, return false
                return false;
            }
        }

        // Finally, remove the now-empty directory
        if (!@rmdir($directory)) {
            return false;
        }

        return true;
    }

    /**
     * Get the listing of extensions.
     *
     * @return array
     */
    public function load(): self
    {
        // Load included listing
        $this->listing = json_decode(file_get_contents($this->pluginDir . '/listing.cfg'), true);

        // Load additional listings
        foreach($this->Config->get('extensions','listings') as $listingURL => $listingToken){

            // Retrieve the listing
            foreach($this->retrieve($listingURL, $listingToken) as $type => $extensions) {

                // Check if the type is already defined
                if(!array_key_exists($type, $this->listing)) {

                    // Initialize the type if not defined
                    $this->listing[$type] = [];
                }

                // Merge the extensions into the type
                foreach($extensions as $base => $extension){

                    // Check if the extension is already defined
                    if(array_key_exists($base, $this->listing[$type])) {

                        // Skip if already exists
                        continue;
                    }

                    // Add the extension to the listing
                    $this->listing[$type][$base] = $extension;
                }
            }
        }

        // Load the extensions
        foreach($this->listing as $type => $extensions) {

            // Check if the type is already defined
            if(!array_key_exists($type, $this->extensions)) {

                // Initialize the type if not defined
                $this->extensions[$type] = [];
            }

            // Add the extensions to the type
            foreach($extensions as $base => $extension){

                // Check if the extension is already defined
                if(array_key_exists($base, $this->extensions[$type])) {

                    // Skip if already exists
                    continue;
                }

                // Set the default values for the extension
                $this->extensions[$type][$base] = $this->defaults($type, $base);

                // Add the extension to the extensions
                foreach($this->retrieve($extension['url'], $extension['token'] ?? null) as $key => $value) {

                    // Check if the key exists in the extension
                    if(array_key_exists($key, $this->extensions[$type][$base])) {

                        // Set the value in the extension
                        $this->extensions[$type][$base][$key] = $value;
                    }
                }

                // Set installed switch
                $this->extensions[$type][$base]['installed'] = false;
            }

            // Load other extensions from the filesystem
            $path = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $type;

            // Check if the path is a directory
            if(is_dir($path)) {

                // Scan the directory for extensions
                $extensions = array_diff(scandir($path), ['..', '.', '.DS_Store']);

                // Add the extensions to the type
                foreach($extensions as $base){

                    // Set some switches
                    $published = array_key_exists($base, $this->extensions[$type]);

                    // Set the info path
                    $infoPath = $path . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . 'info.cfg';

                    // Set the git path
                    $gitPath = $path . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . '.git';

                    // Check if the extension is already defined
                    if(!array_key_exists($base, $this->extensions[$type]) || count($this->extensions[$type][$base]) < 2){

                        // Set the default values for the extension
                        $this->extensions[$type][$base] = $this->defaults($type, $base);
                    }

                    // Check if the info file exists
                    if(file_exists($infoPath)) {

                        // Add the extension to the extensions
                        foreach(json_decode(file_get_contents($infoPath)) as $key => $value) {

                            // Check if the key is version
                            if($key === 'version') {

                                // Set the current version
                                $this->extensions[$type][$base]['current'] = $value;
                            } else {

                                // Check if the key exists in the extension
                                if(array_key_exists($key, $this->extensions[$type][$base])) {

                                    // Set the value in the extension
                                    $this->extensions[$type][$base][$key] = $value;
                                }
                            }
                        }
                    }

                    // Compare the current version with the latest version and set the latest version
                    if(isset($this->extensions[$type][$base]['version']) && version_compare($this->extensions[$type][$base]['current'], $this->extensions[$type][$base]['version'], '<')) {
                        $this->extensions[$type][$base]['latest'] = false;
                    } else {
                        $this->extensions[$type][$base]['latest'] = true;
                    }

                    // Set the extension switches
                    $this->extensions[$type][$base]['published'] = $published;
                    $this->extensions[$type][$base]['initialized'] = is_dir($gitPath);
                    $this->extensions[$type][$base]['installed'] = true;
                }
            }

            // Sort the extensions by name
            ksort($this->extensions[$type]);
        }

        return $this;
    }

    /**
     * Get the listing/details of extensions.
     *
     * @param string      $type  The type of extensions to fetch (e.g., 'modules', 'plugins', 'themes').
     * @param string|null $base  The base name of the extension to fetch (optional).
     * @return array
     */
    public function get(string $type, ?string $base = null): array
    {
        // Check if the listing is loaded
        if(empty($this->extensions)) {
            $this->load();
        }

        // Check if the type exists
        if(!array_key_exists($type, $this->extensions)) {
            return [];
        }

        // If no base is provided, return all extensions of the type
        if($base === null) {
            return $this->extensions[$type];
        }

        // Check if the base exists
        if(!array_key_exists($base, $this->extensions[$type])) {
            return [];
        }

        // Return the extension
        return $this->extensions[$type][$base];
    }

    /**
     * Get the listing of all extensions.
     *
     * @param string|null $type  The type of extensions to fetch (e.g., 'modules', 'plugins', 'themes').
     * @param string|null $base  The base name of the extension to fetch (optional).
     * @param array       $meta  Additional metadata to include in the info file.
     * @return array
     */
    public function meta(string $type, string $base, array $meta): bool
    {
        // Set Default info;
        $info = $this->defaults($type, $base);

        // Replace the info with the data provided
        foreach($this->get($type, $base) as $key => $value) {
            if(array_key_exists($key, $info)) {
                $info[$key] = $value;
            }
        }

        // Replace the info with the data provided
        foreach($meta as $key => $value) {
            if(array_key_exists($key, $info)) {
                $info[$key] = $value;
            }
        }

        // Convert the info to JSON
        $json = json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Set the paths
        $infoPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . 'info.cfg';
        $gitPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $base . DIRECTORY_SEPARATOR . '.git';

        // Return true if the file was written successfully, false otherwise
        return is_dir($gitPath) ? file_put_contents($infoPath, $json) !== false : false;
    }

    /**
     * Publish an extension to the repository.
     *
     * @param string $type  The type of extension (e.g., 'modules', 'plugins', 'themes').
     * @param string $base  The base name of the extension to publish.
     * @return bool         True if the extension was published successfully, false otherwise.
     * @throws RuntimeException If the repository is not set or if the extension is already published.
     */
    public function publish(string $type, string $base): bool
    {
        // Retrieve the extension info
        $extension = $this->get($type, $base);

        // Initialize variables
        $owner = null;
        $repo  = null;
        $url   = null;
        $token = null;

        // Check if the repository is set
        if(!isset($extension['repository']) || empty($extension['repository'])) {
            throw new RuntimeException("The repository for the extension {$base} is not set.");
        }

        // Check if the extension is already published
        if($extension['published']) {
            throw new RuntimeException("The extension {$base} is already published.");
        }

        // Check if the repository is from GitHub
        if(preg_match('#^https?://github\.com/#', $extension['repository'])) {

            // Extract the repository owner and name
            if(preg_match('#^https?://github\.com/([^/]+)/([^/]+)(?:\.git)?$#', $extension['repository'], $matches)) {
                $owner = $matches[1];
                $repo  = $matches[2];
                $url   = "https://api.github.com/repos/{$owner}/{$repo}/contents/info.cfg?ref={$extension['branch']}";
                $token = $extension['token'];
            } else {
                throw new RuntimeException("The repository URL {$extension['repository']} is not a valid GitHub repository.");
            }
        }

        // Check if the repository is from GitLab
        elseif(preg_match('#^https?://gitlab\.com/#', $extension['repository'])) {

            // Extract the repository owner and name
            if(preg_match('#^https?://gitlab\.com/([^/]+)/([^/]+)(?:\.git)?$#', $extension['repository'], $matches)) {
                $owner = $matches[1];
                $repo  = $matches[2];
                $url   = "https://gitlab.com/api/v4/projects/{$owner}%2F{$repo}/repository/files/info.cfg/raw?ref={$extension['branch']}";
                $token = $extension['token'];
            } else {
                throw new RuntimeException("The repository URL {$extension['repository']} is not a valid GitLab repository.");
            }
        }

        // Check if the repository is from Bitbucket
        elseif(preg_match('#^https?://bitbucket\.org/#', $extension['repository'])) {

            // Extract the repository owner and name
            if(preg_match('#^https?://bitbucket\.org/([^/]+)/([^/]+)(?:\.git)?$#', $extension['repository'], $matches)) {
                $owner = $matches[1];
                $repo  = $matches[2];
                $url   = "https://api.bitbucket.org/2.0/repositories/{$owner}/{$repo}/src/{$extension['branch']}/info.cfg";
                $token = $extension['token'];
            } else {
                throw new RuntimeException("The repository URL {$extension['repository']} is not a valid Bitbucket repository.");
            }
        }

        // Check if the URL is set
        if($url){

            // Set in the listing
            $this->listing[$type][$base] = ['url'    => $url];

            // Set the token if available
            if($token) {
                $this->listing[$type][$base]['token'] = $token;
            }

            // Sort the listing by name
            ksort($this->listing[$type]);

            // Set the paths
            $path = $this->pluginDir . '/listing.cfg';

            // Convert the info to JSON
            $json = json_encode($this->listing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            // Return true if the file was written successfully, false otherwise
            return file_put_contents($path, $json) !== false;
        }

        // Return false
        return false;
    }

    /**
     * Unpublish an extension from the repository.
     *
     * @param string $type  The type of extension (e.g., 'modules', 'plugins', 'themes').
     * @param string $base  The base name of the extension to unpublish.
     * @return bool         True if the extension was unpublished successfully, false otherwise.
     */
    public function unpublish(string $type, string $base): bool
    {
        // Check if the listing is loaded
        if(empty($this->listing)) {
            $this->load();
        }

        // Check if the type exists
        if(!array_key_exists($type, $this->listing)) {
            return false;
        }

        // Check if the base exists
        if(!array_key_exists($base, $this->listing[$type])) {
            return false;
        }

        // Remove the extension from the listing
        unset($this->listing[$type][$base]);

        // Sort the listing by name
        ksort($this->listing[$type]);

        // Set the paths
        $path = $this->pluginDir . '/listing.cfg';

        // Convert the info to JSON
        $json = json_encode($this->listing, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Return true if the file was written successfully, false otherwise
        return file_put_contents($path, $json) !== false;
    }

    /**
     * Install an extension.
     *
     * @param string $type  The type of extension (e.g., 'modules', 'plugins', 'themes').
     * @param string $base  The base name of the extension to install.
     * @return bool         True if the extension was installed successfully, false otherwise.
     * @throws RuntimeException If the download or unpacking fails.
     */
    public function install(string $type, string $base): bool
    {
        // Retrieve the extension info
        $extension = $this->get($type, $base);

        // Set the paths
        $tmpPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'tmp';
        $archivePath = $tmpPath . DIRECTORY_SEPARATOR . $base . '.zip';
        $installPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $base;

        // Download the extension's archive
        if($this->download($extension['download'], $archivePath, $extension['token'] ?? null)){

            // Unpack the archive
            if($this->unpack($archivePath, $installPath)){
                return true;
            } else {
                throw new RuntimeException("Failed to unpack the extension {$base}.");
            }
        } else {
            throw new RuntimeException("Failed to download the extension {$base}.");
        }

        return false;
    }

    /**
     * Uninstall an extension.
     *
     * @param string $type  The type of extension (e.g., 'modules', 'plugins', 'themes').
     * @param string $base  The base name of the extension to uninstall.
     * @return bool         True if the extension was uninstalled successfully, false otherwise.
     */
    public function uninstall(string $type, string $base): bool
    {
        // Retrieve the extension info
        $extension = $this->get($type, $base);

        // Set the paths
        $installPath = $this->Config->root() . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR . $base;

        // Return
        return $this->delete($installPath);
    }

    public function update(string $type, string $base): bool
    {
        // Retrieve the extension info
        $extension = $this->get($type, $base);
    }
}
