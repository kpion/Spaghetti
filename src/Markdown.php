<?php 
namespace Kpion\Spaghetti;

use \Exception;

/** 
 * Markdown related utils 
 * Just utilities, the main class you'll deal with is at the end.
*/
class Markdown extends AbstractFormatter
{
    // Escapes **all** markdown special characters, i.e. converts .e.g `#` to `\#`. Exaggerated (defensive) version:
    // public function escape(string $text):string {
    //     return str_replace([
    //       '\\', '-', '#', '*', '+', '`', '.', '[', ']', '(', ')', '!', '&', '<', '>', '_', '{', '}', '|'], [
    //       '\\\\', '\-', '\#', '\*', '\+', '\`', '\.', '\[', '\]', '\(', '\)', '\!', '\&', '\<', '\>', '\_', '\{', '\}', '\|',
    //     ], $text);
    // }czy
    
    // Escapes **all** markdown special characters, i.e. converts .e.g `#` to `\#`. Only characters that are **truly** important in Markdown are escaped
    public function escape(string $text): string {
        // Escape special characters without dashes and pluses
        $escaped = str_replace([
            '\\', '#', '*', '`', '[', ']', '(', ')', '!', '&', '<', '>', '_', '{', '}', '|'
        ], [
            '\\\\', '\#', '\*', '\`', '\[', '\]', '\(', '\)', '\!', '\&', '\<', '\>', '\_', '\{', '\}', '\|'
        ], $text);
    
        // Escaping dashes and pluses only at the beginning of the line
        $escaped = preg_replace('/^(\s*)([-+])/m', '$1\\\$2', $escaped);
    
        return $escaped;
    }
    
    /**
     * Converts an array of rows into a Markdown table.
     * Handles escaping and truncating of values to fit within the table.
     *
     * @param array $rows Each row should be an associative array with headers as keys.
     * @param int $valueMaxLength Maximum length of a cell value before truncation.
     * @return string Markdown table as a string.
     * 
     * Example output:
     * |this|will|make
     * |---|---|---
     * |a|md|table
     */    
    public function table (array $rows, int $valueMaxLength = 1000):string 
    {
        $headers = array_keys($rows[0]);
        $markdown = "| " . implode(" | ", $headers) . " |\n";
        $markdown .= "| " . str_repeat("--- | ", count($headers)) . "\n";

        foreach ($rows as $row) {
            $markdownRow = array_map(fn($value) => $this->sanitize($value, maxLength:$valueMaxLength), $row);
            $markdown .= "| " . implode(" | ", $markdownRow) . " |\n";
        }

        return $markdown;
    }

}
