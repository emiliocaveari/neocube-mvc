<?= '<?php' ?>

use NeoCube\Db\Migration\Migration;

class <?= $className ?> extends Migration {

    public function up() {

        $this->db->exec("CREATE TABLE IF NOT EXISTS <?= $table ?> (
            id SERIAL PRIMARY KEY,
            code INTEGER NOT NULL,
            name VARCHAR(60) NOT NULL,
            descripton TEXT NULL,
            status SMALLINT NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL DEFAULT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT code_unique UNIQUE (code))");
    }

    public function down() {
        $this->db->exec("DROP TABLE <?= $table ?>");
    }

}
