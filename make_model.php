<?php

/**
 * Usage: php make_model.php ModelName [-t] [-p]
 * 
 * -t: Create table
 * -p: Create pages
 */


if ($argc < 2) {
    echo "Usage: php make_model.php ModelName [-t] [-p]\n";
    exit(1);
}

$modelName = $argv[1];
$createTable = in_array('-t', $argv);
$createPages = in_array('-p', $argv);
$directory = __DIR__ . '/Model';

if (!is_dir($directory)) {
    mkdir($directory, 0777, true);
}

// Basit bir çoğul yapma fonksiyonu
function pluralize($word)
{
    $lastLetter = strtolower($word[strlen($word) - 1]);
    switch ($lastLetter) {
        case 'y':
            return substr($word, 0, -1) . 'ies';
        case 's':
        case 'x':
        case 'z':
        case 'h':
            return $word . 'es';
        default:
            return $word . 's';
    }
}

$tableName = pluralize(strtolower($modelName));

$modelTemplate = <<<EOT
<?php

//namespace Model;

//use Model;

class $modelName extends Model
{
    protected \$table = '$tableName';
    
    public function __construct()
    {
        parent::__construct(\$this->table);
    }
}

EOT;

$filePath = "$directory/{$modelName}Model.php";

if (file_exists($filePath)) {
    echo "Model $modelName already exists!\n";
    exit(1);
}

file_put_contents($filePath, $modelTemplate);
echo "Model $modelName created successfully!\n";

define('ROOT', __DIR__);
if ($createTable) {
    require_once ROOT . '/Model/BaseModel.php';
    $db = new Model();
    $sql = "CREATE TABLE IF NOT EXISTS $tableName (
        id INT AUTO_INCREMENT PRIMARY KEY,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->getDb()->exec($sql);
    echo "Table $tableName created successfully!\n";
}

if ($createPages) {

    $pagesDirectory = ROOT . "/pages/$tableName";
    if (!is_dir($pagesDirectory)) {
        mkdir($pagesDirectory, 0777, true);
    }

    $listTemplate = <<<EOT
<?php
// $modelName list page
EOT;

    $manageTemplate = <<<EOT
<?php
// $modelName manage page
EOT;


    if (!file_exists("$pagesDirectory/list.php")) {
        file_put_contents("$pagesDirectory/list.php", $listTemplate);
        echo "List Page for $modelName created successfully!\n";
    } else {
        echo "List Page for $modelName already exists!\n";
    }

    if (!file_exists("$pagesDirectory/manage.php")) {
        file_put_contents("$pagesDirectory/manage.php", $manageTemplate);
        echo "Manage Page for $modelName created successfully!\n";
    } else {
        echo "Manage Page for $modelName already exists!\n";
    }

}