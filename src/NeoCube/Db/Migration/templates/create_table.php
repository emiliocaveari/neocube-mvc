<?= '<?php' ?>

use NeoCube\Db\Migration\Migration;

class <?= $className ?> extends Migration {
    
    public function up() {

        $this->db->exec("CREATE TABLE IF NOT EXISTS `<?= $table ?>` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `varchar` VARCHAR(60) NOT NULL,
            `tinyint` TINYINT(4) UNSIGNED NOT NULL DEFAULT 1,
            `timestamp_current` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `datetime` DATETIME NOT NULL,
            `char` CHAR(32) NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `varchar_UNIQUE` (`varchar` ASC))
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8");
    }

    public function down() {
        $this->db->exec("DROP TABLE `<?= $table ?>`");
    }
    
}
