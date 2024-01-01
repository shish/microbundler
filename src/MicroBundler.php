<?php

namespace MicroBundler;

use FFSPHP\VLQ;
use FFSPHP\Paths;

class MicroBundler
{
    /** @var string[] */
    private array $sources = [];
    private bool $debug = false;

    public function __construct()
    {
    }

    public function addSource(string $filename, ?string $content = null): void
    {
        if($content === null) {
            $content = file_get_contents($filename);
            if($content === false) {
                throw new \Exception("file_get_contents failed");
            }
        }
        $this->sources[$filename] = $content;
    }

    public static function process_file(string $text, string $inpath, string $outpath): string
    {
        if(str_ends_with($outpath, ".css")) {
            // given a url relative to the input file, convert it to a url relative to the output file
            $text2 = preg_replace_callback(
                '/url\(([^)]+)\)/',
                function ($matches) use ($inpath, $outpath) {
                    $url_relative_to_input = trim($matches[1], "'\"");
                    if(strpos($url_relative_to_input, "://") !== false || str_starts_with($url_relative_to_input, "data:")) {
                        return "url('$url_relative_to_input')";
                    }
                    $url_relative_to_base = dirname($inpath) . "/" . $url_relative_to_input;
                    $url_relative_to_output = Paths::relative_path($url_relative_to_base, $outpath);
                    return "url($url_relative_to_output)";
                },
                $text
            );
            if($text2 === null) {
                throw new \Exception("preg_replace_callback failed");
            }
            $text = $text2;
            //$text = trim($text);
        }
        return $text;
    }

    /**
     * @return mixed[] [string $data, array $map]
     */
    public function gen(string $gen_filename): array
    {
        $data = "";
        $gen_line_infos = [];
        $source_no = 0;
        $prev_source_line_count = 0;
        foreach ($this->sources as $source_filename => $source_content) {
            $source_content = static::process_file($source_content, $source_filename, $gen_filename);
            foreach(explode("\n", $source_content) as $source_line_no => $source_line) {
                $data .= $source_line . "\n";
                // For the first line in each file
                if($source_line_no == 0) {
                    $gen_line_infos[] = [[
                        // we always output new files at column 0
                        0,
                        // If this is the first source file, then we are source file #0,
                        // else we are previous source file number + 1
                        $source_no == 0 ? 0 : 1,
                        // Reset the line counter back to zero from the previous file
                        -$prev_source_line_count,
                        // we always read input files from column 0
                        0
                    ]];
                }
                // For all other lines in each file
                else {
                    // output column 0, same source file, line number++, input column 0
                    $gen_line_infos[] = [[0, 0, 1, 0]];
                }
            }
            $prev_source_line_count = $source_line_no;
            $source_no++;
        }

        if(str_ends_with($gen_filename, ".css")) {
            $data .= "/*# sourceMappingURL=" . basename($gen_filename) . ".map */";
        }
        if(str_ends_with($gen_filename, ".js")) {
            $data .= "//# sourceMappingURL=" . basename($gen_filename) . ".map";
        }

        $map = [
            "version" => 3,
            "file" => basename($gen_filename),
            "sources" => array_map(fn ($s) => Paths::relative_path($s, $gen_filename), array_keys($this->sources)),
            "names" => [],
            "mappings" => implode(";", array_map(fn ($line_infos) => implode(",", array_map(fn ($line_info) => VLQ::encode_vlq_array($line_info), $line_infos)), $gen_line_infos)),
        ];
        if($this->debug) {
            $map["sourcesContent"] = array_values($this->sources);
            $map["x_mappings"] = $gen_line_infos;
        }

        return [$data, $map];
    }

    public function save(string $output): void
    {
        [$data, $map] = $this->gen($output);
        file_put_contents($output, $data);
        file_put_contents("$output.map", json_encode($map, $this->debug ? JSON_PRETTY_PRINT : 0));
    }
}
