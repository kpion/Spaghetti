<?php 
namespace Kpion\Spaghetti;

use \Exception;
use \PDO;
use \PDOException;

/*
    Database related.
*/
class Database
{
    // PDO connection, initialized via connect()
    public ?PDO $pdo = null;
    public ?AbstractFormatter $formatter = null;

    public function __construct (?array $dbConfig = null, ?AbstractFormatter $formatter = null)
    {
        $this->formatter = $formatter?:new Markdown();
        if($dbConfig){
            $this->connect($dbConfig);
        }
    }

    /**
     * Connect to the database. Use this before `sql` or any other db functions.
     * @param array $config db configuration: [
     *  'dsn'=>'mysql:host=localhost;dbname=database-name;charset=utf8mb4',
     *  'user'=>'user', 
     *  'password'=>'pass'
     * ]
     *  
    */ 
    public function connect(array $config):void
    {
        try {
            $this->pdo = new PDO($config['dsn'], $config['user'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            echo "Database connection error: " . $e->getMessage() . "\n";
        }
    }

    // Executes a SQL statement and returns the result in a Markdown table format.
    public function sql(string $sql, int $valueMaxLength = 1000): string {
        if ($this->pdo === null) {
            return "Database connection is not set. Use ->connect. \n";
        }        
        try {
            $result = $this->pdo->query($sql);
            $rows = $result->fetchAll();

            if (empty($rows)) {
                return "No results found for query: `$sql`\n";
            }

            // Markdown table format
            return $this->formatter->table($rows,valueMaxLength:$valueMaxLength);
        } catch (\Exception $e) {
            return "SQL query error: " . $e->getMessage() . "\n";
        }
    }

    // Show the `SHOW CREATE TABLE` result.
    public function showCreateTable(string $tableName): string {
        if ($this->pdo === null) {
            return "Database connection is not set. Use ->connect. \n";
        }        
        try {
            $stmt = $this->pdo->query("SHOW CREATE TABLE `$tableName`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($result && isset($result['Create Table'])) {
                return "```sql\n" . $result['Create Table'] . "\n```\n";
            } else {
                return "No results found for table: `$tableName`.\n";
            }
        } catch (Exception $e) {
            return "SQL query eshowrror (SHOW CREATE TABLE): " . $e->getMessage() . "\n";
        }
    }    

    // Show a detailed description of a table's columns.
    public function describeTable(string $tableName): string {
        return $this->sql("DESCRIBE `$tableName`");
    }

    // Show indexes of a table in Markdown format.
    public function indexes(string $tableName): string {
        return $this->sql("SHOW INDEXES FROM `$tableName`");
    }
    

    /**
     * Generate a complete description for a table including structure, indexes, and creation SQL.
     * 
     * @param string $tableName table name
     * @param string $mode 'create' will use `SHOW CREATE TABLE`, 'describe' will use DESCRIBE `$tableName`. Can be false.
    */
    public function table(string $tableName, ?string $mode = 'create', $indexes = false, int $recordsCount = 0): string {
        $tableName = trim($tableName);
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $tableName)) {
            return "Error: Invalid table name\n";
        }        
        $output = "## Table: `$tableName`\n\n";
        if(!empty($mode)){
            $output .= "### Table Structure\n";
            if($mode === 'create'){
                $output .= $this->showCreateTable($tableName) . "\n";
            }elseif ($mode === 'describe'){
                $output .= $this->describeTable($tableName) . "\n";
            }else{
                $output .= "Error: unknown mode: $mode \n";
            }
        }
        
        if($indexes){
            $output .= "### Indexes\n";
            $output .= $this->indexes($tableName) . "\n";
        }

        if($recordsCount){
            $output .= "### $recordsCount example records\n";
            $output .= $this->sql("SELECT * FROM $tableName LIMIT $recordsCount", valueMaxLength:60); 
        }


        return $output;
    }
}
