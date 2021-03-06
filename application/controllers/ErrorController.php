<?php
/**
 * Zend Framework and MongoDB Testing
 *
 * LICENSE: http://www.tomshaw.info/license
 *
 * @category   Tom Shaw
 * @package    Zend Framework Error Handler
 * @copyright  Copyright (c) 2011 Tom Shaw. (http://www.tomshaw.info)
 * @license    http://www.tomshaw.info/license   BSD License
 * @version    $Id:$
 * @since      File available since Release 1.0
 */
class ErrorController extends Zend_Controller_Action
{
	/**
	 * Total lines of code to display.
	 * 
	 * @var int
	 */
	const SOURCE_LINES = 12;

    public function errorAction()
    {
        $errors = $this->_getParam('error_handler');
        
        if (!$errors || !$errors instanceof ArrayObject) {
            $this->view->message = 'You have reached the error page';
            return;
        }
        
        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ROUTE:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $this->getResponse()->setHttpResponseCode(404);
                $priority = Zend_Log::NOTICE;
                $this->view->message = 'Page not found';
                break;
            default:
                // application error
                $this->getResponse()->setHttpResponseCode(500);
                $priority = Zend_Log::CRIT;
                $this->view->message = 'Application error';
                break;
        }
        
        // Log exception, if logger available
        if ($log = $this->getLog()) {
            $log->log($this->view->message, $priority, $errors->exception);
            $log->log('Request Parameters', $priority, $errors->request->getParams());
        }
        
        // conditionally display exceptions
        if ($this->getInvokeArg('displayExceptions') == true) {
            $this->view->exception = $errors->exception;
        }
        
        $this->view->request   = $errors->request;
        
		$this->view->type = $errors->type;
        
		$this->view->file = $errors->exception->getFile();
        
		$this->view->line = $errors->exception->getLine();
        
		$this->view->trace = $errors->exception->getTrace();
		
		$this->view->trace_string = $errors->exception->getTraceAsString();
        
		$file = (isset($this->view->trace[0]['file'])) ? $this->view->trace[0]['file'] : $errors->exception->getFile();
		$line = (isset($this->view->trace[0]['line'])) ? $this->view->trace[0]['line'] : $errors->exception->getLine();
        
        $this->view->source = $this->formatSource($file,$line,self::SOURCE_LINES);
    }

    public function getLog()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        if (!$bootstrap->hasResource('Log')) {
            return false;
        }
        $log = $bootstrap->getResource('Log');
        return $log;
    }
    
    /**
     * 
     * Formats where the location where the error occured.
     * 
     * @param string $file
     * @param int $line
     * @param int $spread
     */
    private function formatSource($file = null, $line = null, $spread = 10)
    {
		$lines = file($file);
		
		$start = 0;
		if($line - $spread >= 0) {
			$start = $line - $spread;
		}
		
		$end = count($lines);
    	if($line + $spread <= $end) {
			$end = $line + $spread;
		}
		
		$out = '';
		for ($i = $start; $i < $end; $i++) {
			$out.= ($line === $i+1) ? '<strong><i>' . $i . '.</i></strong>' . $lines[$i] : $i . $lines[$i]; 
		}
		
		return $out;
	}


}

