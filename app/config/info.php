<?php 
/**
 * Site Information Configuration
 *
 * This file defines global site configuration variables such as the base URL and website name.
 * It dynamically calculates the base URL based on the server environment.
 *
 * @package    GogoAnime Clone
 * @subpackage Configuration
 * @author     GogoAnime Clone Contributors
 * @license    MIT License
 */

/**
 * @var string $base_url The dynamic base URL of the website, including protocol, host, and port.
 */
$base_url = "//" . $_SERVER['SERVER_NAME'] . (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] != '80' && $_SERVER['SERVER_PORT'] != '443' ? ":{$_SERVER['SERVER_PORT']}" : "");

/**
 * @var string $website_name The display name of the website.
 */
$website_name = "GogoAnime";
?>
