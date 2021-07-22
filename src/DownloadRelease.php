<?php

namespace Drutiny\LocalPhpSecurityChecker;

use Composer\Script\Event;

class DownloadRelease {
  const GITHUB_RELEASE_URL = 'https://api.github.com/repos/fabpot/local-php-security-checker/releases/latest';
  const BIN = 'local-php-security-checker';

  public static function go(Event $event)
  {
    $context = stream_context_create(
        array(
            "http" => array(
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
            )
        )
    );

    $event->getIO()->write("Getting local-php-security-checker Releases....");
    $release = file_get_contents(DownloadRelease::GITHUB_RELEASE_URL, false, $context);
    $release = json_decode($release, true);

    $os = strtolower(php_uname('s'));

    foreach ($release['assets'] as $asset) {
      if (strpos($asset['browser_download_url'], $os) !== FALSE) {
        $asset_url = $asset['browser_download_url'];
        $asset_name = $asset['name'];
        break;
      }
    }

    if (!isset($asset_url)) {
      $event->getIO()->writeError("No downloadable asset found for https://github.com/fabpot/local-php-security-checker");
      exit(1);
    }

    $bin_dir = $event->getComposer()->getConfig()->get('bin-dir');
    $base_dir = dirname($event->getComposer()->getConfig()->get('vendor-dir'));

    $symlink = $bin_dir . '/' . DownloadRelease::BIN;
    $filepath = $bin_dir . '/' . $asset_name;

    !file_exists($bin_dir) && mkdir($bin_dir, 0777, true);

    $event->getIO()->write("Downloading $asset_name...");
    file_put_contents($filepath, file_get_contents($asset_url));

    file_exists($symlink) && unlink($symlink);

    chdir($bin_dir);
    $event->getIO()->write("Symlinking $asset_name to {bin-dir}/" . DownloadRelease::BIN);
    symlink($asset_name, DownloadRelease::BIN);

    chmod($filepath, 0755);
  }
}