<?php

namespace NeoCube\Error;

enum ErrorType {

    case CONNECTION;
    case EXCEPTION;
    case HANDLER;
    case SHUTDOWN;
    case FATAL;
    case WARNING;
    case STARTING;
    case INTERNAL;

}