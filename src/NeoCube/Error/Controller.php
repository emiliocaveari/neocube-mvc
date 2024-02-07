<?php

namespace NeoCube\Error;

use NeoCube\Application;

class Controller {

    static final public function handler($errno,$errstr,$errfile=null,$errline=null,$errcontext=[]){
        $error = array(
            'code'    => $errno,
            'message' => $errstr,
            'file'    => $errfile,
            'line'    => $errline,
            'context' => $errcontext
        );
        Application::ErrorReporting()->dispatch($error,ErrorType::HANDLER);
    }
    static final public function shutdown(){
        $error = error_get_last();
        if ($error){
            $error['code'] = $error['type'];
            switch ($error['type']) {
                case E_ERROR:
                    Application::ErrorReporting()->dispatch($error,ErrorType::SHUTDOWN);
                    break;
                case E_WARNING:
                    Application::ErrorReporting()->dispatch($error,ErrorType::SHUTDOWN);
                    break;
            }
        }
    }

}
