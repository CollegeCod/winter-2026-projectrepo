<?php
/**
 * config.php
 * Central configuration file for the system.
 */

define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_PORT", 587);

//Must be empty when uploaded to github
// Should be Client Email
define("SMTP_USERNAME", "");

// Must be empty when uploaded to github
// No Spaces
// 2 factor authentication must be enabled, then an app password can be generated through google account manager
// used in place of actual password so emails can be sent
// For client email
define("SMTP_APP_PASSWORD", "");

// Used as business name in email
// Must be empty when uploaded to github
define("SMTP_FROM_NAME", "");
