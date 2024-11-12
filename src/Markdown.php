<?php 
namespace Kpion\Spaghetti;

use \Exception;
/** 
 * Markdown related utils 
 * Just utilities, the main class you'll deal with is at the end.
*/
class Markdown
{

    /*
    |this|will|make
    |---|---|---
    |a|md|table
    */
    public function table (array $rows, int $valueMaxLength = 1000):string 
    {
        $headers = array_keys($rows[0]);
        $markdown = "| " . implode(" | ", $headers) . " |\n";
        $markdown .= "| " . str_repeat("--- | ", count($headers)) . "\n";

        foreach ($rows as $row) {
            $markdownRow = array_map(fn($value) => $this->format($value, maxLength:$valueMaxLength), $row);
            $markdown .= "| " . implode(" | ", $markdownRow) . " |\n";
        }

        return $markdown;
    }

    // Escapes all markdown special characters, i.e. converts .e.g `#` to `\#`
    public function escape(string $text):string {
        return str_replace([
          '\\', '-', '#', '*', '+', '`', '.', '[', ']', '(', ')', '!', '&', '<', '>', '_', '{', '}', '|'], [
          '\\\\', '\-', '\#', '\*', '\+', '\`', '\.', '\[', '\]', '\(', '\)', '\!', '\&', '\<', '\>', '\_', '\{', '\}', '\|',
        ], $text);
    }

    // Length limit.
    public function limit (string $text, int $maxLength, ?string $dots = '...'): string {
        if ($maxLength && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength) . $dots??'';
        }
        return $text;
    }

    // Length limit+escape+replace null with 'null'+maybe other stuff
    public function format(?string $text, int $maxLength = null): string {
        if ($text === null) {
            $text = 'NULL';
        }
        if($maxLength !== null){
            $text = $this->limit($text, $maxLength);
        }
        $text = $this->escape($text);
        return $text;
    }    
}
