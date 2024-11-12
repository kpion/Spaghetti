# Spaghetti - Lightweight Documentation Tool

Create markdown files with a little help of PHP


Version 0.1.

Work in progress.

**Spaghetti** is a small, PHP-based tool designed to generate Markdown documentation for individual topics or code snippets. While larger projects may benefit from more extensive tools like Sphinx, **Spaghetti** offers a quick and simple way to document code, database structures, and directory contents in Markdown format.

## Installation

Install globally with Composer:

```bash
composer global require kpion/spaghetti
```
Make sure your global Composer binaries directory is in your system's PATH.

## Features

- Generate Markdown documentation from PHP files with embedded code.
- Supports displaying database schema details and SQL table structures.
- Provides a snapshot of directory structures, useful for project organization.

## Example Usage

Create a file called .e.g. **test.md.spaghetti** :
```
# This is a Spaghetti test

We can use regular **markdown** syntax, plus PHP functions. For example to include a different md file:
<?= $spaghetti->file('another-file.md')?>
```
Then build it:
`/path/to/spaghetti test.md.spaghetti`



### Including External Files

Easily include contents of external files to keep your documentation updated without manual copy-pasting. For example:

```php
<?= Spaghetti::file('../src/Entity/User.php'); ?>
```

This includes the contents of `User.php` directly in the Markdown output.

### Database Table Structure Example

You can use `showCreateTable` to generate a Markdown-friendly representation of your table’s SQL schema:

```php
<?= Spaghetti::showCreateTable('example_table'); ?>
```

Assuming `example_table` is defined in your database, the output might look like this:

```sql
CREATE TABLE `example_table` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Available Functions

Here are some of the core functions available in **Spaghetti** for generating documentation content:

- **File Inclusion**:
  `Spaghetti::file($path)` - Includes the contents of the specified file.

- **SQL Table Schema**:
  `Spaghetti::showCreateTable($tableName)` - Shows the SQL `CREATE TABLE` statement for the specified table.

- **SQL Query Execution**:
  `Spaghetti::sql($query, $valueLengthLimit = 1000)` - Executes an SQL query and returns results as a Markdown table.

- **Directory Structure**:
  `Spaghetti::directoryStructure($directory, $depth = 2, $exclude = ['.git', 'vendor'])` - Shows a tree-like structure of directories with optional depth and exclusion parameters.

- **Table Description**:
  `Spaghetti::describeTable($tableName)` - Displays column details of a table, such as types and keys.

- **Table Indexes**:
  `Spaghetti::showIndexes($tableName)` - Lists all indexes of the specified table in Markdown format.

- **Complete Table Documentation**:
  `Spaghetti::describeFullTable($tableName)` - Combines the table structure, `CREATE TABLE` statement, and indexes in a single output for complete table documentation.

---

## Getting Started

1. Clone the repository.
2. Ensure PHP is installed on your system.
3. Run the `process-Spaghetti` script with your `.Spaghetti` file as an argument to generate the Markdown output:

```bash
process-Spaghetti your_file.Spaghetti > output.md
```

### Contributing

This project was built with collaboration and feedback from the community. Contributions, suggestions, and improvements are always welcome!

---

Feel free to use, adapt, and extend **Spaghetti** for your small-scale documentation needs.

