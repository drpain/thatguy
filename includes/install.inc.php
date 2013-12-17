<?php
$installRan = false;
// This will check whether the tables and columns exists and create them if they do not exist.

// This magical function will do a number of things.
// It checks whether the column already exists, if not then it creates it.
// If it does exist, it checks whether the parameters of the columns matches that of specified and then alters it.
function addColumn($table, $column, $options, $defaults) {

    $checkColumn = str_replace('`', '', $column);

    // The mysql_query we build to view the column information.
    $query = mysql_query('SHOW COLUMNS FROM ' . $table .  ' like "' . $checkColumn . '"') or die ("You want me to add a column, but the table does not exists.");

    // The variables used to loop through the entire result for a match
    $found = 0;
    $correctColumns = 0;

   // Actuall loop to run through the columns
   while(list($field, $type, $null, $key, $default, $extra) = mysql_fetch_array($query)) {

      // If a match is found mark it for after the loop processing.
      if ($field == $checkColumn) {
        $found = $found + 1;

        // We found a matching column cool, now we need to check the type and mark it for post loop processing if needed.
        if ($type !== $options){
           $correctColumns = $correctColumns + 1;
        }
      }
   }

   // Post loop processing begins here
   if ($found > 0) {

      // So we found the column, lets check if it needs modification.
      if ($correctColumns > 0) {

        // Run the modification query so we set it to the type we want.
        $query = "ALTER TABLE $table MODIFY $column $options $defaults";
        //echo $query;
        mysql_query($query);
        //echo("UPDATE!, Modified column $column in the $table table ERROR: " . mysql_error() . "<br />");
      }

    // No column was found, now we will need to add it.
    } else {

        // The column add query.
        $query = "ALTER TABLE $table ADD COLUMN $column $options $defaults";
        mysql_query($query);
        //echo("UPDATE! Added column $column to the $table table ERROR: " . mysql_error() . "<br />");
    }
}

// This function will create the table if it does not exits
function createTable($table, $column, $primary, $auto, $collate='') {
   if ($table !== '') {

      $addAuto = "";
      $addPrim = "";

      // Check if it's a primary key
      if ($primary == True) {
         $addPrim = " PRIMARY KEY";
      }

      // Check if it's an auto increment
      if ($auto == True) {
        $addAuto = " AUTO_INCREMENT";
      }

      $query = "CREATE TABLE IF NOT EXISTS " . $table . " (" . $column . $addPrim . $addAuto . ")" . $collate;
      //echo $query;
      $result = mysql_query($query) or die ("Could not create table " . $table);
      //echo("UPDATE!, Added table " . $table . mysql_error());
   }
}

// If no tales exists, then we need to create as a minimum the options table
$db = Database::getDatabase();
$db->query('SHOW TABLES like "options"');
if(!$db->hasRows())
{
    $query = "CREATE TABLE `options` (
            `key` varchar(255) NOT NULL PRIMARY KEY,
            `value` TEXT NOT NULL default '')";
    mysql_query($query) or die ('Could not create the options table, please ensure the mysql details are correct. SQL ERROR:' . mysql_error());
}

clearstatcache(); // Clear the stats
$lastEdit = stat('includes/install.inc.php');
$lastEdit = $lastEdit['mtime'];
$fileSize = filesize('includes/install.inc.php');

// Get the options from the db, will by default have a false value
$dbInstallDate = Options::get('installModified');
$dbInstallFilesize = Options::get('installFilesize');

// Force the filesize and modification date to strings to be able to compare it with the DB
settype($lastEdit, 'string');
settype($fileSize, 'string');

// Update version based on the date and / or filesize. This will also work if the db is empty
if (($dbInstallDate !== $lastEdit)
or ($dbInstallFilesize !== $fileSize))
{

// Create the activation table and add the columns
createTable("activation", "id INT(255) UNSIGNED NOT NULL", True, True);
addColumn("activation", 'user', "int(255) UNSIGNED", "default 0");
addColumn("activation", 'code', "varchar(50)", "default ''");
addColumn("activation", 'active', "tinyint(1) UNSIGNED", "default 0");

// Create the sessions table
createTable("sessions", "id varchar(255) NOT NULL", True, False);
addColumn('sessions', 'data', "text", "default NULL");
addColumn('sessions', 'updated_on', "int(10) UNSIGNED", "default 0");

// Create the url_cache table
createTable("url_cache", "url varchar(255) NOT NULL", True, false, "ENGINE='MyISAM'");
addColumn("url_cache", 'dt_refreshed', "datetime", "default NULL");
addColumn("url_cache", 'dt_expires', "datetime", "default NULL");
addColumn("url_cache", 'data', "text", "default NULL");

// Create the users table
createTable("users", "id int(11) UNSIGNED NOT NULL", True, True);
addColumn("users", 'nid', "varchar(32)", "default NULL");
addColumn("users", 'username', "varchar(65)", "default NULL");
addColumn("users", 'password', "varchar(65)", "default NULL");
addColumn("users", 'level', "ENUM('free', 'user', 'admin', 'moderator')", "default 'user'");
addColumn("users", 'email', "varchar(65)", "default NULL");

// Create the cache table
createTable("cache", "id int(11) UNSIGNED NOT NULL", True, True);
addColumn("cache", 'file', "varchar(32)", "default ''");
addColumn("cache", 'size', "int(30) UNSIGNED", "default NULL");
addColumn("cache", '`timestamp`', "int(30)", "default ".time());

// Create the blog table
createTable("blog", "id int(11) UNSIGNED NOT NULL", True, True);
addColumn("blog", 'user_id', "int(11) UNSIGNED", "default NULL");
addColumn("blog", '`desc`', "text", "default NULL");
addColumn("blog", '`body`', "text", "default NULL");
addColumn("blog", 'timestamp', "int(30) UNSIGNED", "default ".time());

// Create the blog_uploads table, used when uploading images to blogs
createTable("blog_uploads", "id int(11) UNSIGNED NOT NULL", True, True);
addColumn("blog_uploads", 'user_id', "int(11) UNSIGNED", "default NULL");
addColumn("blog_uploads", 'blog_id', "int(11) UNSIGNED", "default NULL");
addColumn("blog_uploads", 'image', "varchar(100)", "default ''");

// Create the blog_comments table, used for when someone comments on a blog post
createTable("blog_comments", "id int(11) UNSIGNED NOT NULL", True, True);
addColumn("blog_comments", 'user_id', "int(11) UNSIGNED", "default NULL");
addColumn("blog_comments", 'blog_id', "int(11) UNSIGNED", "default NULL");
addColumn("blog_comments", '`comment`', "text", "default NULL");
addColumn("blog_comments", '`timestamp`', "int(30) UNSIGNED", "default " . time());
addColumn("blog_comments", 'username', "varchar(100)", "default ''");

// Add the options table columns
addColumn("options", '`type`', "ENUM('input', 'date', 'bool', 'hidden', 'textarea')", "default 'input'");
addColumn("options", '`group`', "varchar(65)", "default 'Miscellaneous'");

// This table will be where the settings are kept
createTable("options_groups", "`group` varchar(65) NOT NULL", True, False);
addColumn("options_groups", '`desc`', "varchar(255)", "default NULL");

// This is where we will add the clien'ts ability to add values to the settings.
createTable("options_users", "user_id int(11) UNSIGNED NOT NULL", false, false);
addColumn("options_users", '`key`', "varchar(65)", "default NULL");
addColumn("options_users", 'value', "TEXT", "default ''");


// Create the first tv column we will need
createTable("tv", "id int(11) UNSIGNED NOT NULL", true, true);
addColumn("tv", 'seriesid', "int(11) UNSIGNED", "default NULL");
addColumn("tv", '`seriesname`', "varchar(255)", "NOT NULL default ''");
addColumn("tv", '`banner`', "varchar(255)", "NOT NULL default ''");
addColumn("tv", '`poster`', "varchar(255)", "NOT NULL default ''");
addColumn("tv", '`rating`', "int(10) UNSIGNED", "NOT NULL default 0");
addColumn("tv", '`overview`', "TEXT", "default NULL");
addColumn("tv", '`status`', "varchar(30)", "NOT NULL default ''");
addColumn("tv", '`last_updated`', "varchar(255)", "NOT NULL default ''");

// Create the first tv column we will need
createTable("tv_favorites", "id int(11) UNSIGNED NOT NULL", true, true);
addColumn("tv_favorites", 'seriesid', "int(11) UNSIGNED", "default NULL");
addColumn("tv_favorites", 'user_id', "int(11) UNSIGNED", "default NULL");

// We also need the episode column
createTable("tv_episode", "id int(11) UNSIGNED NOT NULL", true, true);
addColumn("tv_episode", 'seriesid', "int(11) UNSIGNED", "default NULL");
addColumn("tv_episode", 'episodeid', "int(11) UNSIGNED", "default NULL");
addColumn("tv_episode", '`name`', "varchar(255)", "NOT NULL default ''");
addColumn("tv_episode", '`overview`', "TEXT", "default NULL");
addColumn("tv_episode", '`banner`', "varchar(255)", "NOT NULL default ''");
addColumn("tv_episode", 's', "int(11) UNSIGNED", "default NULL"); // Season
addColumn("tv_episode", 'e', "int(11) UNSIGNED", "default NULL"); // Episode
addColumn("tv_episode", '`date`', "int(100) UNSIGNED", "default NULL"); // Episode

// Create the first tv column we will need
createTable("tv_alert", "id int(11) UNSIGNED NOT NULL", true, true);
addColumn("tv_alert", 'episodeid', "int(11) UNSIGNED", "default NULL");
addColumn("tv_alert", 'emailed', "int(1) UNSIGNED", "default 0");
addColumn("tv_alert", 'online', "int(1) UNSIGNED", "default 0");
addColumn("tv_alert", '`timestamp`', "int(30) UNSIGNED", "default " . time());
addColumn("tv_alert", 'user_id', "int(11) UNSIGNED", "default NULL");

$installRan = true;

} // End of installation action
?>