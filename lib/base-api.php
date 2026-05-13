<?php
/**
 * @author Bryan Lee <yeebwn@gmail.com>
 */

require_once __DIR__.'/http-errors.php';
require_once __DIR__.'/student.php';

abstract class BaseAPI {

    protected $db;
    protected $env;
    protected $method;
    protected $isPost;
    protected $isGet;
    protected $isProduction;
    protected $isDevelopment;
    protected $isStaging;
    protected $testStaffId;
    protected $testStaffRole;
    protected $testStudentId;
    protected $testStudentEmail;
    protected $testStudentPassword;
    protected $bypassAuth;
    protected $command;
    protected $input;
    protected $basePath;
    protected $adminPath;
    protected $returnUrl;

    protected abstract function onRun();

    /**
     * BaseAPI constructor.
     * @param array $settings
     */
    function __construct($settings) {
        session_start();
        try {
            $this->prepare($settings);
            $this->onRun();
        } catch(PDOException $e) {
            $this->responseError(500, 'Internal Server Error (DB)', $e);
        } catch(HTTPError $e) {
            $this->responseHTTPError($e);
        } catch(Exception $e) {
            $this->responseError(500, 'Internal Server Error', $e);
        }
    }

    protected function redirect($url) {
        header("location: {$url}");
        exit();
    }

    protected function getSess($name, $def = '') {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $def;
    }

    protected function setSess($name, $value) {
        $_SESSION[$name] = $value;
    }

    /**
     * @param string $name
     * @param string $def
     * @return string
     */
    protected function getQs($name, $def = '') {
        return isset($_GET[$name]) ? $_GET[$name] : $def;
    }

    protected function hasInput($name) {
        return isset($this->input[$name]);
    }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    protected function getInput($name) {
        if(!isset($this->input[$name])) {
            throw new Exception("Input[`{$name}`] is missing");
        }
        return $this->input[$name];
    }

    /**
     * @param string $args,...
     * @return array
     */
    protected function getInputArray($args) {
        $names = func_get_args();
        $input = array();
        foreach($names as $name) {
            $input[$name] = $this->getInput($name);
        }
        return $input;
    }

    /**
     * @param array $data
     * @throws Exception
     */
    protected function responseJson($data) {
        /*
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: DELETE, HEAD, GET, OPTIONS, POST, PUT');
        header('Access-Control-Allow-Headers: Content-Type, Content-Range, Content-Disposition, Content-Description');
        header('Access-Control-Max-Age: 1728000');
        */
        header('Content-Type: application/json');
        $res = json_encode($data);
        if(!$res) {
            throw new Exception('Invalid characters in data set. Please contact Bryan or Peter.');
        }
        echo $res;
        exit();
    }

    /**
     * @param HTTPError $e
     * @throws Exception
     */
    private function responseHTTPError($e) {
        $statusCode = $e->getStatusCode();
        $statusText = $e->getStatusText();
        $this->responseError($statusCode, $statusText, $e);
    }

    /**
     * @param int $statusCode
     * @param string $statusText
     * @param Exception $e
     * @throws Exception
     */
    private function responseError($statusCode, $statusText, $e) {
        header("HTTP/1.0 {$statusCode} {$statusText}");
        if($this->isProduction) {
            $this->responseJson(array(
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                //'trace' => $e->getTrace()
            ));
        } else {
            $this->responseJson(array(
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'trace' => $e->getTrace()
            ));
        }
    }

    /**
     * @param array $settings
     * @throws Exception
     */
    private function prepare($settings) {
        $env = $settings['env'];
        $method = $_SERVER['REQUEST_METHOD'];
        if($method == 'OPTIONS') {
            $this->responseJson(array('message'=>'OK', 'status'=>200));
        }
        $isPost = $method === 'POST';
        $isGet = $method === 'GET';
        $input = null;
        if($isPost) {
            $input = json_decode(file_get_contents('php://input'), true);
            if(json_last_error() != JSON_ERROR_NONE) {
                throw new BadRequest();
            }
        }
        $this->env = $env;
        $this->method = $method;
        $this->isPost = $isPost;
        $this->isGet = $isGet;
        $this->basePath = $settings['basePath'];
        $this->adminPath = $settings['adminPath'];
        $this->returnUrl = $settings['returnUrl'];

        $this->isProduction = $env == 'production';
        $this->isDevelopment = $env == 'development';
        $this->isStaging = $env == 'staging';
        if($this->isDevelopment || $this->isStaging) {
            $this->testStaffId = $settings['testing']['staffId'];
            $this->testStaffRole = $settings['testing']['staffRole'];
            $this->testStudentId = $settings['testing']['studentId'];
            $this->testStudentEmail = $settings['testing']['email'];
            $this->testStudentPassword = $settings['testing']['password'];
            $this->bypassAuth = $settings['bypassAuth'];
        }

        $this->command = $this->getQs('cmd');
        $this->input = $input;

        if($settings['pdo']['database'] == 'mysql') {
            $db = new PDO($settings['pdo']['dsn'], $settings['pdo']['user'], $settings['pdo']['pass']);
        } else {
            $db = new PDO($settings['pdo']['dsn']);
        }
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->db = $db;
    }

}
