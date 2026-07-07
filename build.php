<?php
/**
 * @package     Joomill Access Key plugin - Build script
 * @author      Jeroen Moolenschot | Joomill
 * @copyright   Copyright (c) 2026 Joomill Extensions. All rights reserved.
 * @license     GNU General Public License version 3 or later
 *
 * Builds correctly named installable zips into dist/:
 *
 *   <name>_v<version><suffix>.zip     e.g. plg_system_accesskey_v2.2.1.zip
 *
 * The name is derived from each extension's manifest (type/group/element), the
 * version from its <version> tag. Bumping the version stays a manual step. At
 * build time the manifest <creationDate> is stamped with the current month and
 * year (e.g. "July 2026") so the packaged and installed extension show the
 * release date.
 *
 * Usage (from this folder):
 *   php build.php              build everything (including the package, when configured)
 *   php build.php <filter>     build only extensions whose folder or zip name contains <filter>
 *
 * Joomill build rules (vault: 30-snippets/joomla-package-build.md):
 *   - only run this script on explicit request, never automatically
 *   - requires PHP CLI with the zip extension (extension=zip in php.ini)
 */

// ---------------------------------------------------------------------------
// Configuration (per repo) - the engine below is identical in every repo.
// ---------------------------------------------------------------------------

$config = [
    // Appended to every zip name ('' for FREE/standalone, '_PRO' for PRO repos).
    'suffix' => '',

    // Extensions to build. Per entry:
    //   dir      folder with the extension source ('.' = repo root)
    //   name     zip base name; omit to derive from the manifest
    //   package  path of this extension's zip inside the package dir (omit when not packaged)
    //   exclude  extra exclude patterns for this extension
    'extensions' => [
        ['dir' => '.'],
    ],

    // Package build ('dir' holds the package manifest), or null.
    'package' => null,
];

// ---------------------------------------------------------------------------
// Engine - do not edit below this line; keep in sync across all Joomill repos.
// ---------------------------------------------------------------------------

// Paths that must never end up inside a distributed zip. Matched against the
// path relative to the extension folder, using fnmatch() (so * and ? work).
// NOTE: CLAUDE.md / CHANGELOG.md / README.md are dev-facing and excluded on
// purpose; CLAUDE.md may document internals that must not ship to customers.
$defaultExcludes = [
    '.git', '.git/*', '.gitignore', '.gitattributes',
    '.idea', '.idea/*', '.junie', '.junie/*', '.claude', '.claude/*',
    '.example', '.example/*', '.phpunit.cache', '.phpunit.cache/*', '.phpunit.result.cache',
    '.gitkeep', '*/.gitkeep', '.DS_Store', '*/.DS_Store',
    'CLAUDE.md', 'CHANGELOG.md', 'README.md',
    'docs', 'docs/*', 'tests', 'tests/*', 'vendor', 'vendor/*', 'node_modules', 'node_modules/*',
    'build.php', 'build_all.py', 'build', 'build/*', 'dist', 'dist/*',
    'composer.json', 'composer.lock', 'composer.phar', 'phpunit.xml.dist',
    '*.zip', '*.iml', '*.psd', '*.py',
];

if (!class_exists('ZipArchive')) {
    fwrite(STDERR, "ERROR: PHP ZipArchive extension is not enabled (add extension=zip to php.ini).\n");
    exit(1);
}

$repoDir = __DIR__;
$distDir = $repoDir . '/dist';
$suffix  = $config['suffix'] ?? '';
$filter  = isset($argv[1]) ? strtolower($argv[1]) : null;

if (!is_dir($distDir) && !mkdir($distDir, 0755, true)) {
    fwrite(STDERR, "ERROR: could not create output dir: $distDir\n");
    exit(1);
}

$built  = 0;
$errors = 0;

// -- Extensions -------------------------------------------------------------

foreach ($config['extensions'] as $extension) {
    $dirRel = $extension['dir'];
    $dir    = $dirRel === '.' ? $repoDir : $repoDir . '/' . $dirRel;

    if (!is_dir($dir)) {
        fwrite(STDERR, "ERROR missing extension folder: $dirRel\n");
        $errors++;
        continue;
    }

    try {
        $manifestPath = find_manifest($dir);
    } catch (RuntimeException $e) {
        fwrite(STDERR, 'ERROR ' . $dirRel . ' : ' . $e->getMessage() . "\n");
        $errors++;
        continue;
    }

    $xml     = simplexml_load_file($manifestPath);
    $version = trim((string) $xml->version);
    $name    = $extension['name'] ?? derive_name($xml, $manifestPath);

    if ($version === '') {
        fwrite(STDERR, "ERROR $dirRel : could not read <version> from " . basename($manifestPath) . "\n");
        $errors++;
        continue;
    }

    if ($filter !== null
        && strpos(strtolower(basename($dirRel)), $filter) === false
        && strpos(strtolower($name), $filter) === false) {
        continue;
    }

    stamp_creation_date($manifestPath);

    $excludes = array_merge($defaultExcludes, $extension['exclude'] ?? []);
    $zipPath  = $distDir . '/' . $name . '_v' . $version . $suffix . '.zip';

    $fileCount = zip_dir($dir, $zipPath, $excludes);

    if ($fileCount === false) {
        fwrite(STDERR, "ERROR $dirRel : could not create " . basename($zipPath) . "\n");
        $errors++;
        continue;
    }

    printf("OK    %-38s -> %s  (%d files)\n", $dirRel, basename($zipPath), $fileCount);
    $built++;

    // Copy the zip into the package folder when this extension is part of the package.
    if (!empty($extension['package']) && !empty($config['package'])) {
        $pkgDir    = $config['package']['dir'] === '.' ? $repoDir : $repoDir . '/' . $config['package']['dir'];
        $targetZip = $pkgDir . '/' . $extension['package'];

        if (!is_dir(dirname($targetZip))) {
            mkdir(dirname($targetZip), 0755, true);
        }

        copy($zipPath, $targetZip);
        printf("      %-38s -> %s\n", '', $config['package']['dir'] . '/' . $extension['package']);
    }
}

// -- Package ----------------------------------------------------------------

if (!empty($config['package'])) {
    if ($filter !== null) {
        echo "NOTE  package skipped because a filter is active; run without arguments to rebuild it.\n";
    } else {
        $pkgDirRel = $config['package']['dir'];
        $pkgDir    = $pkgDirRel === '.' ? $repoDir : $repoDir . '/' . $pkgDirRel;

        try {
            $manifestPath = find_manifest($pkgDir, 'package');
        } catch (RuntimeException $e) {
            fwrite(STDERR, 'ERROR ' . $pkgDirRel . ' : ' . $e->getMessage() . "\n");
            exit(1);
        }

        $xml     = simplexml_load_file($manifestPath);
        $version = trim((string) $xml->version);
        $name    = derive_name($xml, $manifestPath);

        stamp_creation_date($manifestPath);

        // The package zip contains everything in the package dir except dev
        // files, the extension source dirs and any zip that is not a configured
        // sub-zip of this package.
        $excludes = array_diff($defaultExcludes, ['*.zip']);
        $allowedZips = [];

        foreach ($config['extensions'] as $extension) {
            if (!empty($extension['package'])) {
                $allowedZips[] = str_replace('\\', '/', $extension['package']);
            }

            // Exclude extension source dirs that live inside the package dir.
            $extDir = $extension['dir'] === '.' ? $repoDir : $repoDir . '/' . $extension['dir'];

            if (strpos($extDir . '/', $pkgDir . '/') === 0 && $extDir !== $pkgDir) {
                $rel = str_replace('\\', '/', substr($extDir, strlen($pkgDir) + 1));
                $excludes[] = $rel;
                $excludes[] = $rel . '/*';
            }
        }

        $zipPath   = $distDir . '/' . $name . '_v' . $version . $suffix . '.zip';
        $fileCount = zip_dir($pkgDir, $zipPath, $excludes, $allowedZips);

        if ($fileCount === false) {
            fwrite(STDERR, "ERROR $pkgDirRel : could not create " . basename($zipPath) . "\n");
            $errors++;
        } else {
            printf("OK    %-38s -> %s  (%d files)\n", $pkgDirRel . ' (package)', basename($zipPath), $fileCount);
            $built++;
        }
    }
}

echo "\n";
printf("Done. %d zip(s) written to %s%s\n", $built, $distDir, $errors ? " ($errors error(s))" : '');

exit($errors ? 1 : 0);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Finds the single Joomla manifest (root element <extension>) in a folder.
 * When $type is given, the manifest must also be of that extension type.
 */
function find_manifest(string $dir, ?string $type = null): string
{
    $candidates = [];

    foreach (glob($dir . '/*.xml') as $xmlPath) {
        $sx = @simplexml_load_file($xmlPath);

        if ($sx === false || strtolower($sx->getName()) !== 'extension') {
            continue;
        }

        if ($type !== null && strtolower((string) $sx['type']) !== $type) {
            continue;
        }

        $candidates[] = $xmlPath;
    }

    if (count($candidates) !== 1) {
        throw new RuntimeException('expected exactly one manifest xml, found ' . count($candidates));
    }

    return $candidates[0];
}

/**
 * Derives the canonical zip base name from a manifest: plg_<group>_<element>,
 * mod_<element>, com_<element> or pkg_<packagename>.
 */
function derive_name(SimpleXMLElement $xml, string $manifestPath): string
{
    $type    = strtolower((string) $xml['type']);
    $element = strtolower(pathinfo($manifestPath, PATHINFO_FILENAME));

    switch ($type) {
        case 'plugin':
            return 'plg_' . strtolower((string) $xml['group']) . '_' . $element;
        case 'module':
            return strpos($element, 'mod_') === 0 ? $element : 'mod_' . $element;
        case 'component':
            return strpos($element, 'com_') === 0 ? $element : 'com_' . $element;
        case 'package':
            return strpos($element, 'pkg_') === 0 ? $element : 'pkg_' . $element;
    }

    return $element;
}

/**
 * Stamps the manifest <creationDate> with the current month and year, so the
 * packaged and installed extension carry the release date. Writes the source
 * manifest in place; only when the date actually changes.
 */
function stamp_creation_date(string $manifestPath): void
{
    $currentDate  = date('F Y');
    $manifestBody = file_get_contents($manifestPath);
    $stampedBody  = preg_replace(
        '#(<creationDate>).*?(</creationDate>)#s',
        '${1}' . $currentDate . '${2}',
        $manifestBody,
        1,
        $stampCount
    );

    if ($stampCount && $stampedBody !== $manifestBody) {
        file_put_contents($manifestPath, $stampedBody);
        printf("      creationDate -> %-11s (%s)\n", $currentDate, basename($manifestPath));
    }
}

/**
 * Zips a folder into $zipPath with forward-slash entries (Joomla requirement;
 * PowerShell's Compress-Archive writes backslashes and breaks the installer).
 * Returns the number of files added, or false on failure.
 *
 * When $allowedZips is given, .zip files are only included when they match one
 * of those relative paths (used for the package build).
 */
function zip_dir(string $dir, string $zipPath, array $excludes, ?array $allowedZips = null)
{
    if (file_exists($zipPath)) {
        unlink($zipPath);
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
        return false;
    }

    $inner    = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $filtered = new RecursiveCallbackFilterIterator($inner, function ($current) use ($dir, $excludes, $allowedZips) {
        $relative = str_replace('\\', '/', substr($current->getPathname(), strlen($dir) + 1));

        if (is_excluded($relative, $excludes)) {
            return false;
        }

        if ($allowedZips !== null
            && !$current->isDir()
            && strtolower(substr($relative, -4)) === '.zip'
            && !in_array($relative, $allowedZips, true)) {
            return false;
        }

        return true;
    });

    $fileCount = 0;
    $iterator  = new RecursiveIteratorIterator($filtered, RecursiveIteratorIterator::SELF_FIRST);

    foreach ($iterator as $item) {
        $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($dir) + 1));

        if ($item->isDir()) {
            $zip->addEmptyDir($relative);
        } else {
            $zip->addFile($item->getPathname(), $relative);
            $fileCount++;
        }
    }

    $zip->close();

    return $fileCount;
}

/**
 * Returns true when a relative path matches any exclude pattern.
 * A directory match also excludes everything inside it.
 */
function is_excluded(string $relative, array $excludes): bool
{
    foreach ($excludes as $pattern) {
        if (fnmatch($pattern, $relative) || strpos($relative, rtrim($pattern, '/*') . '/') === 0) {
            return true;
        }
    }

    return false;
}
