<?php

namespace NeoCube\Error;

use NeoCube\Error\ErrorInterface;

abstract class ErrorAbstract implements ErrorInterface {

    protected array $error = [];
    protected ?ErrorType  $type = null;

    public function onError(){}

    //--Final class - não podem ser alteradas pelas classes filhas
    final public function getMessage() :string { return $this->getError('message'); }
    final public function getCode()    :string { return $this->getError('code');    }
    final public function getFile()    :string { return $this->getError('file');    }
    final public function getLine()    :string { return $this->getError('line');    }
    final public function getContext() :string { return $this->getError('context'); }

    final public function getType() : ?ErrorType {
        return $this->type;
    }
    final public function getError($col=null){
        if ($col) return isset($this->error[$col]) ? $this->error[$col] : null;
        else      return $this->error;
    }
    //--Recebe o erro disparado no sistema
    final public function dispatch(\Throwable | array | string $error=null, ?ErrorType $type=null) :void {
        $this->type = $type ?: ErrorType::INTERNAL;
        if ($error instanceOf \Throwable){
            $this->error = array(
                'code'    => $error->getCode(),
                'message' => $error->getMessage(),
                'file'    => $error->getFile(),
                'line'    => $error->getLine(),
                'context' => $error->getTrace(),
            );
        } else if ( is_array($error) ){
            $this->error = array(
                'code'    => $error['code'] ?? '',
                'message' => $error['message'] ?? '',
                'file'    => $error['file'] ?? '',
                'line'    => $error['line'] ?? '',
                'context' => $error['context'] ?? null
            );
        } else {
            $this->error = array(
                'code'    => 'INTERNAL',
                'message' => $error,
                'file'    => null,
                'line'    => null,
                'context' => null
            );
        }
        $this->onError();
    }



}
