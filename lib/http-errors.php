<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

class HTTPError extends Exception {

    private $statusCode;
    private $statusText;

    function __construct($statusCode, $statusText, $message = '', $code = 0, Exception $previous = null) {
        parent::__construct($message ? $message : $statusText, $code, $previous);
        $this->statusCode = $statusCode;
        $this->statusText = $statusText;
    }

    function getStatusCode() {
        return $this->statusCode;
    }

    function getStatusText() {
        return $this->statusText;
    }

}

class BadRequest extends HTTPError {

    function __construct($message = '', $code = 0, Exception $previous = null) {
        parent::__construct(400, 'Bad Request', $message, $code, $previous);
    }

}

class Unauthorized extends HTTPError {

    function __construct($message = '', $code = 0, Exception $previous = null) {
        parent::__construct(401, 'Unauthorized', $message, $code, $previous);
    }

}

class NotFound extends HTTPError {

    function __construct($message = '', $code = 0, Exception $previous = null) {
        parent::__construct(404, 'Not Found', $message, $code, $previous);
    }

}

class InternalServerError extends HTTPError {

    function __construct($message = '', $code = 0, Exception $previous = null) {
        parent::__construct(500, 'Internal Server Error', $message, $code, $previous);
    }

}
