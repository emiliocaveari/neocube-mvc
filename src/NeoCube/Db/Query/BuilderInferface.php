<?php

namespace NeoCube\Db\Query;

interface BuilderInferface {
    public function select(Query $query): string;
    public function insert(Query $query): string;
    public function insertMult(Query $query): string;
    public function update(Query $query): string;
    public function delete(Query $query): string;
    public function lineNumbers(Query $query): string;
}
