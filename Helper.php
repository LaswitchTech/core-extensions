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
    public function __construct() {

        // Call parent constructor
        parent::__construct();

        // Set the plugin directory
        $this->pluginDir = dirname(__FILE__);
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

                // Add the extension to the extensions
                $this->extensions[$type][$base] = $this->retrieve($extension['url'], $extension['token'] ?? null);
            }

            // Sort the extensions by name
            ksort($this->extensions[$type]);
        }

        var_dump($this->extensions);

        return $this;
    }

    /**
     * Get the listing/details of extensions.
     *
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
}
