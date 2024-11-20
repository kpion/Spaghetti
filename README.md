# Spaghetti - Lightweight Documentation Tool

Automate explaining your code to AI (and maybe humans).  
By creating Markdown files with a little help from PHP.

**Version 0.42** (Work in progress)

---

**Spaghetti** is a lightweight PHP-based tool for generating Markdown documentation. Unlike full-scale documentation tools like phpDocumentor, which focus on entire projects, Spaghetti is designed for narrower, context-specific tasks.  

With Spaghetti, you can easily combine your own explanations with dynamically generated content such as:  

- Database table schemas  
- Code snippets from specific files  
- Directory structures  

It’s perfect for creating quick, topic-focused documentation that’s both human-readable and AI-friendly. Whether you’re documenting a single class, explaining a database schema, or just need to save time describing your project in detail, Spaghetti has you covered.

## Example Usage

- Create a file like **about-pet-project.spaghetti.php**:

\# Pet Adoption Project

This is a simple project for managing a pet adoption database. It includes tables for storing information about pets, their sweetness level (subjective but fun!), and more.


- Now create your main file, **index.spaghetti.php**:


 `<?= $spaghetti->import('about-pet-project.spaghetti.php') ?>`

 The most important table in this project is `pet`. Here's its structure:

`<?= $spaghetti->db->showTable('pet') ?>`

It’s connected to the `sweetness` table, which evaluates how adorable each pet is:

`<?= $spaghetti->db->showTable('sweetness') ?>`

Each pet is represented by the `Pet` entity:

`<?= $spaghetti->file('src/Entity/Pet.php') ?>`


Finally, build the documentation using Spaghetti:  
```bash
/path/to/spaghetti index.spaghetti.php > index.md
```

This will generate a Markdown file (`index.md`) with all your descriptions, database schemas, and entity code snippets combined.


## Installation

Installing Spaghetti as a regular Composer dependency is NOT supported. Spaghetti is a tool, not a library. As such, it should be installed as a standalone package, so that Spaghetti's dependencies do not interfere with your project's dependencies.

Install globally with Composer:

```bash
composer global require kpion/spaghetti
```
Make sure your global Composer binaries directory is in your system's PATH.


## Details

### Including External Files

Easily include contents of external files to keep your documentation updated without manual copy-pasting. For example:

```php
<?= $spaghetti->import('../src/Entity/User.php'); ?>
```

This includes the contents of `User.php` directly in the Markdown output.

### Database Table Structure Example

You can use `showCreateTable` to generate a Markdown-friendly representation of your table’s SQL schema:

```php
<?= $spaghetti->showCreateTable('example_table'); ?>
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
  `$spaghetti->import($path)` - Includes the contents of the specified file.

- **SQL Table Schema**:
  `$spaghetti->db->showCreateTable($tableName)` - Shows the SQL `CREATE TABLE` statement for the specified table.

- **SQL Query Execution**:
  `$spaghetti->db->sql($query, $valueLengthLimit = 1000)` - Executes an SQL query and returns results as a Markdown table.

- **Directory Structure**:
  `$spaghetti->dir($directory, $depth = 2, $exclude = ['.git', 'vendor'])` - Shows a tree-like structure of directories with optional depth and exclusion parameters.

- **Table Description**:
  `$spaghetti->db->describeTable($tableName)` - Displays column details of a table, such as types and keys.

- **Table Indexes**:
  `$spaghetti->db->showIndexes($tableName)` - Lists all indexes of the specified table in Markdown format.

- **Complete Table Documentation**:
  `$spaghetti->db->describeFullTable($tableName)` - Combines the table structure, `CREATE TABLE` statement, and indexes in a single output for complete table documentation.

---

## Getting Started

1. Clone the repository.
2. Ensure PHP is installed on your system.
3. Run the `spaghetti` script with your `your-file.spaghetti.php` file as an argument to generate the Markdown output:

```bash
spaghetti your-file.spaghetti.php > your-file.md
```

### Contributing

This project was built with collaboration and feedback from the community. Contributions, suggestions, and improvements are always welcome!

---

Feel free to use, adapt, and extend **Spaghetti** for your small-scale documentation needs.

