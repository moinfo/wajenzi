<?php
/**
 * Permission Format Converter
 *
 * This script finds and converts the old permission format:
 *
 * @if(\App\Models\UsersPermission::isUserAllowed(Auth::user()->id,"CRUD","Add Expense"))
 * @endif
 *
 * To the new Laravel Gate/Policy format:
 * @can('Add Expense')
 * @endcan
 *
 * Usage:
 * 1. Save this script to your Laravel project
 * 2. Run: php permission-converter.php
 */

// Directory to scan recursively (hardcoded to pages directory)
$directory = 'resources/views/forms';

// Permission action types to convert
$actionTypes = ['CRUD', 'Create', 'Read', 'Update', 'Delete', 'View', 'List', 'Export', 'Print', 'Add', 'Edit'];

// Files to check (add more extensions if needed)
$fileExtensions = ['php', 'blade.php'];

// Start the conversion
echo "Starting permission format conversion in: {$directory}\n";
echo "------------------------\n";

$statistics = [
    'files_scanned' => 0,
    'files_modified' => 0,
    'permissions_converted' => 0,
    'errors' => 0,
];

// Process the directory recursively
processDirectory($directory, $fileExtensions, $actionTypes, $statistics);

// Print statistics
echo "\n------------------------\n";
echo "Conversion complete!\n";
echo "Files scanned: {$statistics['files_scanned']}\n";
echo "Files modified: {$statistics['files_modified']}\n";
echo "Permissions converted: {$statistics['permissions_converted']}\n";
echo "Errors: {$statistics['errors']}\n";

/**
 * Process a directory recursively
 */
function processDirectory($directory, $fileExtensions, $actionTypes, &$statistics) {
    $files = scandir($directory);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = $directory . '/' . $file;

        if (is_dir($path)) {
            // Recursively process subdirectories
            processDirectory($path, $fileExtensions, $actionTypes, $statistics);
        } else {
            // Process files with matching extensions
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            if (in_array($extension, $fileExtensions) ||
                in_array('blade.' . $extension, $fileExtensions)) {
                processFile($path, $actionTypes, $statistics);
            }
        }
    }
}

/**
 * Process a single file
 */
function processFile($filePath, $actionTypes, &$statistics) {
    $statistics['files_scanned']++;

    echo "Processing: {$filePath}";

    try {
        $content = file_get_contents($filePath);
        $originalContent = $content;

        // First, identify and collect all the permission check patterns
        $permissionChecks = [];
        $pattern = '/@if\s*\(\s*\\\\App\\\\Models\\\\UsersPermission::isUserAllowed\s*\(\s*Auth::user\(\)->id\s*,\s*"([^"]+)"\s*,\s*"([^"]+)"\s*\)\s*\)(.*?)@endif/s';

        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        // Process in reverse order to avoid position changes affecting other replacements
        for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
            $fullMatch = $matches[0][$i][0];
            $matchPos = $matches[0][$i][1];
            $actionType = $matches[1][$i][0];
            $permission = $matches[2][$i][0];
            $innerContent = $matches[3][$i][0];

            // Create the replacement
            $replacement = '@can(\'' . $permission . '\')' . $innerContent . '@endcan';

            // Calculate replacement positions
            $matchLength = strlen($fullMatch);

            // Replace this specific permission check
            $content = substr_replace(
                $content,
                $replacement,
                $matchPos,
                $matchLength
            );

            $statistics['permissions_converted']++;
        }

        // Only write the file if changes were made
        if ($content !== $originalContent) {
            file_put_contents($filePath, $content);
            $statistics['files_modified']++;
            echo " - CONVERTED\n";
        } else {
            echo " - No changes needed\n";
        }
    } catch (Exception $e) {
        $statistics['errors']++;
        echo " - ERROR: " . $e->getMessage() . "\n";
    }
}
