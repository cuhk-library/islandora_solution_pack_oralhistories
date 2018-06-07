<?php

/**
 * Class VttConverter
 * Adobted from here: https://github.com/mantas-done/subtitles
 */

class VttConverter  {

  public function fileContentToInternalFormat($file_content)
  {
    $internal_format = array(); // array - where file content will be stored

    $blocks = explode("\n\n", trim($file_content)); // each block contains: start and end times + text

    foreach ($blocks as $block) {
      if (trim($block) == 'WEBVTT') {
        continue;
      }
      $lines = explode("\n", $block); // separate all block lines
      $times = explode(' --> ', $lines[0]);
      $name = static::getName($lines[1]);

      $internal_format[] = array(
        'start' => static::vttTimeToInternal($times[0]),
        'end' => static::vttTimeToInternal($times[1]),
        'name' => $name,
        'lines' => array_map(static::fixLine(), array_slice($lines, 1)), // get all the remaining lines from block (if multiple lines of text)
      );
    }

    return $internal_format;
  }

  public function internalFormatToFileContent(array $internal_format)
  {
    $file_content = "WEBVTT\n\n";

    foreach ($internal_format as $k => $block) {
      $start = static::internalTimeToVtt($block['start']);
      $end = static::internalTimeToVtt($block['end']);
      $lines = implode("\n", $block['lines']);

      $file_content .= $start . ' --> ' . $end . "\n";
      $file_content .= $lines . "\n";
      $file_content .= "\n";
    }

    $file_content = trim($file_content);

    return $file_content;
  }

  // ------------------------------ private --------------------------------------------------------------------------

  protected static function vttTimeToInternal($vtt_time)
  {
    $parts = explode('.', '00:' . $vtt_time);

    $only_seconds = strtotime("1970-01-01 {$parts[0]} UTC");
    $milliseconds = (float)('0.' . $parts[1]);

    $time = $only_seconds + $milliseconds;

    return $time;
  }

  protected static function internalTimeToVtt($internal_time)
  {
    $parts = explode('.', $internal_time); // 1.23
    $whole = $parts[0]; // 1
    $decimal = isset($parts[1]) ? $parts[1] : 0; // 23

    $srt_time = gmdate("i:s", floor($whole)) . '.' . str_pad($decimal, 3, '0', STR_PAD_RIGHT);

    return $srt_time;
  }

  protected static function fixLine()
  {
    return function($line) {
      $regex_vtt_name = '/^\s*<v\s+(?<name>.*)>/';
      preg_match($regex_vtt_name, $line, $matches);
      if (array_key_exists('name', $matches)) {
        $line = preg_replace($regex_vtt_name, '', $line);
      }

      return $line;
    };
  }

  protected static function getName($line)
  {
    $regex_vtt_name = '/^\s*<v\s+(?<name>.*)>/';
    preg_match($regex_vtt_name, $line, $matches);

    $name = "";
    if (array_key_exists('name', $matches)) {
      $name = $matches['name'];
    }

    return $name;
  }
}
