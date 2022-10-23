<?php
// Copyright (c) 2011-2020 Peter Olszowka. All rights reserved. See copyright document for more details.

// Load common code.
global $title;
$bootstrap4 = true;
$title = "Configuration Settings";
require_once('StaffCommonCode.php');

if (!(isLoggedIn() && may_I("Administrator"))) {
  staff_header($title, $bootstrap4);
  echo ("<h2>Insufficient privilege.</h2>");
  staff_footer();
  exit(0);
}

define('CONFIG_DIR', 'config/');
define('SAMPLE_CONFIG', CONFIG_DIR.'db_name_sample.php');
define('LIVE_CONFIG', CONFIG_DIR.'db_name.php');
define('TEMP_CONFIG', CONFIG_DIR.'db_name_temp.php');
define('BACKUP_BASE', 'db_name_backup_');
define('BACKUP_PATTERN', '/'.BACKUP_BASE.'(\d+)\.php/');
define('DEFINE_PATTERN', '/^\s*define\(([\'"][A-Za-z0-9_]+[\'"]), ?(.*)\);(.*)$/');
define('STRING_PATTERN', '/^[\'"]?(.*?)[\'"]?$/');
define('INT_PATTERN', '/^\d+$/');
define('DATE_PATTERN', '/^[\'"]?\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}[\'"]?$/');
define('COMMENT_PATTERN', '/^\s*\/\/\s*(.*)$/');

// Load config and process posted values before displaying header.
// If 
$defaultConfig = readConfig(SAMPLE_CONFIG);
$messages = [];
if (!empty($_POST) && isset($_POST['submitbtn'])) {
  // Form has been posted.
  try {
    // Load current live config (we'll only use the prefix lines from this).
    $liveConfig = readConfig(LIVE_CONFIG);
    // Copy current config to backup.
    $backupFile = nextBackupFile();
    copy(LIVE_CONFIG, $backupFile);
    $messages[] = "<p>Current config backed up to <b>$backupFile</b>.</p>";
    // Create new config file.
    $newConfig = implode("\n", $liveConfig->prefix) . "\n";
    foreach ($defaultConfig->defines as $setting) {
      $newConfig .= "define(\"$setting->name\", " . outputType($setting->type, $_POST[$setting->name]) . ");";
      foreach ($setting->comments as $comment)
        $newConfig .= "$comment\n";
    }
    // Save to temporary file first.
    file_put_contents(TEMP_CONFIG, $newConfig);
    // Copy temporary file to live file.
    copy(TEMP_CONFIG, LIVE_CONFIG);
    unlink(TEMP_CONFIG);
    $messages[] = "<p>Config file saved.</p>";
  }
  catch (Exception $e) {
    $messages[] = "<p>Failed writing config file: " . $e->getMessage() . ".</p>";
  }
  // Reload the page so the new values take effect.
  header('Location: ConfigurationAdmin.php');
  exit(0);
}

staff_header($title, $bootstrap4);

/**
 * Infer a string's type from the pattern value.
 */
function getDefineType(string $value): string
{
  $lower = strtolower($value);
  if ($lower == 'true' || $lower == 'false')
    return 'boolean';
  if (preg_match(INT_PATTERN, $value))
    return 'int';
  if (preg_match(DATE_PATTERN, $value))
    return 'datetime';
  return 'string';
}

/**
 * Remove surrounding quotes from string, and convert escaped quotes to plain quotes.
 */
function unwrapString(string $value): string
{
  return str_replace(["\\\"", "\\'"], ["\"", "'"], preg_replace(STRING_PATTERN, '$1', $value));
}

/**
 * Read the PlanZ configuration file into an object containing an array of prefix lines,
 * and an array of defined settings.
 */
function readConfig(string $fileName): object
{
  $lines = file($fileName, FILE_IGNORE_NEW_LINES) or die("Unable to open configuration file.");
  $inPrefix = true;
  $prefixLines = [];
  $defines = [];
  foreach ($lines as $curLine) {
    $matched = preg_match(DEFINE_PATTERN, $curLine, $matches);
    // Prefix lines from the start of file to first define.
    if ($inPrefix && !$matched) {
      $prefixLines[] = $curLine;
    }
    // If line is a define, break into an object.
    if ($matched) {
      $inPrefix = false;
      $defines[] =
        (object)[
          'name' => unwrapString($matches[1]),
          'defaultValue' => unwrapString($matches[2]),
          'type' => getDefineType($matches[2]),
          'comments' => [$matches[3]]
        ];
    }
    // If not a define, and not in prefix, add line to comments of most recent define.
    if (!($inPrefix || $matched)) {
      $defines[count($defines) - 1]->comments[] = $curLine;
    }
  }
  return (object)['prefix' => $prefixLines, 'defines' => $defines];
}

function nextBackupFile(): string
{
  $highest = 0;
  $fileNames = scandir(CONFIG_DIR);
  foreach ($fileNames as $name) {
    if (preg_match(BACKUP_PATTERN, $name, $matches)) {
      if ($matches[1] > $highest)
        $highest = $matches[1];
    }
  }
  return CONFIG_DIR . BACKUP_BASE . ($highest + 1) . '.php';
}

/**
 * Format a boolean field as a Select box, with default value preselected.
 */
function trueFalseField(string $name, bool $value): string
{
  $options = '';
  foreach ([false => 'False', true => 'True'] as $bool=>$label)
    $options .= "<option value=$bool" . ($value == $bool ? ' selected' : '') . ">$label</option>";
  return "<select name=\"$name\" id=\"$name\">$options</select>";
}

/**
 * Format field value for output to config file, depending on type.
 * ToDo: Change type of $value to string|int|bool when we move to PHP 8.
 */
function outputType(string $type, $value): string 
{
  switch ($type) {
    case 'boolean':
      return $value ? 'true' : 'false';
    case 'int':
      return $value ?: '0';
    case 'datetime':
      return "\"" . (new DateTime($value))->format("Y-m-d h:i:s") . "\"";
    default:
      return "\"". str_replace('"', '\"', $value) ."\"";
  }
}

// Display any messages.
foreach ($messages as $message) {
  echo ($message);
}

// Output HTML for the form and table column headers.
echo <<<EOD
  <form name="configuration" method="POST" action="ConfigurationAdmin.php">
    <table class="table table-condensed compressed">
      <thead>
        <tr>
          <th>Name</th>
          <th>Default</th>
          <th>Current</th>
          <th>Comments</th>
        </tr>
      </thead>
      <tbody>
EOD;

// Display a row for each configuration setting.
foreach($defaultConfig->defines as $define) {
  $value = defined($define->name) ? constant($define->name) : '';
  $default = htmlspecialchars($define->defaultValue);
  // ToDo: Replace with match() when we move to PHP8.
  switch ($define->type) {
    case 'boolean':
      $field = trueFalseField($define->name, $value);
      break;
    case 'int':
      $field = "<input type=\"number\" id=\"$define->name\" name=\"$define->name\" step=\"1\" value=\"$value\" >";
      break;
    case 'datetime':
      $field = "<input type=\"datetime-local\" id=\"$define->name\" name=\"$define->name\" value=\"$value\" >";
      break;
    default:
      $value = htmlspecialchars($value);
      $field = "<input type=\"text\" id=\"$define->name\" name=\"$define->name\" value=\"$value\" >";
  }
  // Strip out leading // from comment lines, and concatenate with <br> tags.
  $comments = implode('<br />', array_map(fn(string $line): string => preg_replace(COMMENT_PATTERN, '$1', $line), $define->comments));
  echo <<<EOD
        <tr>
          <td><label for=\"$define->name\">$define->name</label></td>
          <td>$default</td>
          <td>$field</td>
          <td>$comments</td>
        </tr>
  EOD;
}
// Output table closure and buttons.
echo <<<EOD
      </tbody>
    </table>
    <div class="row justify-content-center mt-4">
      <div class="col-auto">
        <button type="submit" class="btn btn-secondary" id="resetbtn" name="resetbtn" value="undo">Reset</button>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-primary" id="submitbtn" name="submitbtn" value="save">Save</button>
      </div>
    </div>
  </form>
EOD;

staff_footer();
?>