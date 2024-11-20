<?php 
namespace Kpion\Spaghetti;

use \Exception;


abstract class AbstractFormatter {

    /** 
     * Escapes characters specific to the implemented format
     * For example, for Markdown, it escapes all markdown special characters (converts e.g. `#` to `\#`)
    */
    abstract public function escape(string $text): string;

    /**
    * Converts an array of rows into a Markdown table.
    * Handles escaping and truncating of values to fit within the table.
    */
    abstract public function table (array $rows, int $valueMaxLength = 1000): string; 
    
    // Length limit.
    public function limit (string $text, int $maxLength, ?string $dots = '...'): string {
        if ($maxLength && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength) . $dots??'';
        }
        return $text;
    }


    // Length limit+escape+replace null with 'null'+maybe other stuff
    public function sanitize(?string $text, int $maxLength = null): string {
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
