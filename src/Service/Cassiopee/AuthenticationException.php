<?php 

namespace App\Service\Cassiopee;

class AuthenticationException extends \Exception {

    public function __construct(string $message = "", $code = 0, \Throwable $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}