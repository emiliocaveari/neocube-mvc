<?php
namespace NeoCube\Db\Migration;


abstract class Migration
{
    public function __construct(protected $db)
    {
        
    }

    public function up()
    {
        return true;
    }

    public function down()
    {
        return true;
    }

}
