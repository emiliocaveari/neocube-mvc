<?= '<?php' ?>

use NeoCube\Db\Migration\Migration;

class <?= $className ?> extends Migration {
    
    public function up() {

        $this->db->exec("CREATE TABLE IF NOT EXISTS `<?= $table ?>` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` INT(10) NOT NULL,
            `name` VARCHAR(60) NOT NULL,
            `descripton` TEXT NULL,
            `status` TINYINT(4) UNSIGNED NOT NULL DEFAULT 1,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `deleted_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE INDEX `code_UNIQUE` (`code` ASC))
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8");
    }

    public function down() {
        $this->db->exec("DROP TABLE `<?= $table ?>`");
    }
    
}
