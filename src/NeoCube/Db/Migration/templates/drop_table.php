<?= '<?php' ?>

use NeoCube\Db\Migration\Migration;

class <?= $className ?> extends Migration {

    public function up() {
        $this->db->exec("DROP TABLE `<?= $table ?>`");
    }

    public function down() {
        return false;
    }

}
