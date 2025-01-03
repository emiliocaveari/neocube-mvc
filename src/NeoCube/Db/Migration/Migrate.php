<?php

namespace NeoCube\Db\Migration;

use NeoCube\Db\Connection;

final class Migrate {

    const BASE_MIGRATION = 'm000000_000000_base';

    public $migrationTable = 'migrations';

    public $templateFiles = [
        'create_table' => __DIR__ . '/templates/create_table.php',
        'add_column'   => __DIR__ . '/templates/add_column.php',
        'drop_column'  => __DIR__ . '/templates/drop_column.php',
        'drop_table'   => __DIR__ . '/templates/drop_table.php',
        '__default__'  => __DIR__ . '/templates/default.php',
    ];

    public string $migrationsPath;

    private $_db = null;


    public function __construct($config = []) {

        if (php_sapi_name() !== 'cli')
            throw new \Exception('Only support execute by Cli.');

        $this->_db = $config['db'] ?? Connection::factory();
        $this->migrationsPath = $config['migrationsPath'] ?? './migrations';

        if ($this->_db->getAttribute(\PDO::ATTR_DRIVER_NAME) !== 'mysql')
            throw new \Exception('Only support MySQL driver.');

        $this->_init();
    }

    public function create($name) {
        $dirlocal = getcwd();
        $pathlocal = realpath($this->migrationsPath);
        $className = 'm' . gmdate('ymd_His') . '_' . $name;
        $fileName = $this->migrationsPath . '/' . $className . '.php';
        if ($this->_confirm("Create new migration '{$fileName}'?")) {
            file_put_contents(
                $fileName,
                $this->_generateMigrationSourceCode($className, $name)
            );
        }
    }

    public function up($limit = null) {
        $migrations = array_slice($this->_getNew(), 0, $limit);
        if (!$this->_confirm(
            "Total " . count($migrations) . " new migration to be applied:\n    "
                . implode("\n    ", array_map(function ($migration) {
                    return basename($migration);
                }, $migrations))
                . "\nApply the above migration?"
        )) {
            return "No effect";
        }

        foreach ($migrations as $migration) {
            try {
                require $migration;
                $name = substr(basename($migration), 0, -4);
                $className = '\\' . $name;
                $instance = new $className($this->_db);
                if ($instance->up() !== false) {
                    $this->_toUp($name);
                } else {
                    return 'ERROR';
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    public function down($limit = 1) {
        $migrations = array_slice(array_reverse($this->_getHistory()), 0, $limit);
        if (!$this->_confirm(
            "Total " . count($migrations) . " history migration to be applied:\n    "
                . implode("\n    ", array_map(function ($row) {
                    return '[' . date('Y-m-d H:i:s', $row['apply_time']) . '] ' . $row['name'];
                }, $migrations))
                . "\nDown the above migration?"
        )) {
            return "No effect";
        }

        foreach (array_column($migrations, 'name') as $name) {
            try {
                require $this->migrationsPath . '/' . $name . '.php';
                $className = '\\' . $name;
                $instance = new $className($this->_db);
                if ($instance->down() !== false) {
                    $this->_toDown($name);
                } else {
                    return 'ERROR';
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
    }

    public function mark($name, $applied = true) {
        if (!$this->_migrationExists($name)) {
            return "Migration '{$name}' not exists";
        }

        $result = false;
        if ($applied) {
            $this->_confirm("Mark migration '{$name}' to applied ?")
                and $result = $this->_toUp($name);
        } else {
            $this->_confirm("Mark migration '{$name}' to down ?")
                and $result = $this->_toDown($name);
        }

        return "Mark {$name} to " . ($applied ? 'applied' : 'down') . ' ' . ($result ? 'success' : 'failed');
    }

    public function new() {
        $new = "\nShowing the new migrations:";
        foreach ($this->_getNew() as $file) {
            $new .= "\n    " . basename($file);
        }

        return $new."\n\n";
    }

    public function history() {
        $history = "\nShowing the applied migrations:";
        foreach ($this->_getHistory() as $row) {
            $history .= "\n    [" . date('Y-m-d H:i:s', $row['apply_time']) . '] ' . $row['name'];
        }

        return $history."\n\n";
    }

    private function _migrationExists($name) {
        return file_exists("{$this->migrationsPath}/{$name}.php");
    }

    private function _init() {
        $this->_db->exec("CREATE TABLE IF NOT EXISTS {$this->migrationTable} (
          `name` varchar(180) NOT NULL,
          `apply_time` int(11) DEFAULT NULL,
          PRIMARY KEY (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        $this->_toUp(self::BASE_MIGRATION);
    }

    private function _toUp($name) {
        try {
            $stm = $this->_db->prepare("INSERT INTO {$this->migrationTable} set `name` = ?, `apply_time` = ?");
            $stm->execute([$name, time()]);
        } catch (\PDOException $e) {
            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function _toDown($name) {
        try {
            $stm = $this->_db->prepare("DELETE FROM {$this->migrationTable} WHERE `name` = ?");
            $stm->execute([$name]);
        } catch (\PDOException $e) {
            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function _getHistory() {
        $stm = $this->_db->prepare("SELECT * FROM {$this->migrationTable} WHERE `name` <> :base ORDER BY `apply_time` ASC");
        $stm->bindValue(':base', self::BASE_MIGRATION);
        $stm->execute();
        return $stm->fetchAll();
    }

    private function _getNew() {
        $history = array_column($this->_getHistory(), 'name');
        $migrations = glob($this->migrationsPath . '/*');
        $new = array_filter($migrations, function ($migration) use ($history) {
            return !in_array(substr(basename($migration), 0, -4), $history);
        });

        usort($new, function ($migrationA, $migrationB) {
            return strnatcasecmp(basename($migrationA), basename(basename($migrationB)));
        });

        return $new;
    }

    private function _renderTemplate($file, $params) {
        ob_start();
        ob_implicit_flush(false);
        extract($params, EXTR_OVERWRITE);
        require($file);

        return ob_get_clean();
    }

    private function _generateMigrationSourceCode($className, $name) {
        $templateFile = $this->templateFiles['__default__'];
        $table = null;
        if (preg_match('/^create_(.+)_table$/', $name, $matches)) {
            $templateFile = $this->templateFiles['create_table'];
            $table = strtolower($matches[1]);
        } elseif (preg_match('/^add_columns?_to_(.+)_table$/', $name, $matches)) {
            $templateFile = $this->templateFiles['add_column'];
            $table = strtolower($matches[1]);
        } elseif (preg_match('/^drop_columns?_from_(.+)_table$/', $name, $matches)) {
            $templateFile = $this->templateFiles['drop_column'];
            $table = strtolower($matches[1]);
        } elseif (preg_match('/^drop_(.+)_table$/', $name, $matches)) {
            $templateFile = $this->templateFiles['drop_table'];
            $table = strtolower($matches[1]);
        }

        return $this->_renderTemplate($templateFile, [
            'table' => $table,
            'className' => $className,
        ]);
    }

    public function _confirm($text) {
        global $argv;
        if (in_array('--interactive=0', $argv)) {
            return true;
        }

        echo "{$text} (yes|no) [no]:";
        $confirmation = trim(fgets(STDIN));
        return $confirmation and $confirmation[0] === 'y';
    }
}
