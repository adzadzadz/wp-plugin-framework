<?php

namespace AdzWP;

class Log 
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    
    protected static $instance = null;
    protected $logPath;
    protected $logFile = 'adz-plugin.log';
    protected $dateFormat = 'Y-m-d H:i:s';
    protected $enabled = true;
    protected $minLevel = self::DEBUG;
    protected $maxFileSize = 10485760; // 10MB
    
    protected $levels = [
        self::EMERGENCY => 0,
        self::ALERT     => 1,
        self::CRITICAL  => 2,
        self::ERROR     => 3,
        self::WARNING   => 4,
        self::NOTICE    => 5,
        self::INFO      => 6,
        self::DEBUG     => 7,
    ];
    
    public function __construct()
    {
        $uploadDir = wp_upload_dir();
        $this->logPath = $uploadDir['basedir'] . '/adz-logs/';
        
        if (!file_exists($this->logPath)) {
            wp_mkdir_p($this->logPath);
            file_put_contents($this->logPath . '.htaccess', 'Deny from all');
            file_put_contents($this->logPath . 'index.php', '<?php // Silence is golden');
        }
    }
    
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    public function emergency($message, array $context = [])
    {
        return $this->log(self::EMERGENCY, $message, $context);
    }
    
    public function alert($message, array $context = [])
    {
        return $this->log(self::ALERT, $message, $context);
    }
    
    public function critical($message, array $context = [])
    {
        return $this->log(self::CRITICAL, $message, $context);
    }
    
    public function error($message, array $context = [])
    {
        return $this->log(self::ERROR, $message, $context);
    }
    
    public function warning($message, array $context = [])
    {
        return $this->log(self::WARNING, $message, $context);
    }
    
    public function notice($message, array $context = [])
    {
        return $this->log(self::NOTICE, $message, $context);
    }
    
    public function info($message, array $context = [])
    {
        return $this->log(self::INFO, $message, $context);
    }
    
    public function debug($message, array $context = [])
    {
        return $this->log(self::DEBUG, $message, $context);
    }
    
    public function log($level, $message, array $context = [])
    {
        if (!$this->enabled) {
            return false;
        }
        
        if (!$this->shouldLog($level)) {
            return false;
        }
        
        $logFile = $this->logPath . $this->logFile;
        
        $this->rotateLogFile($logFile);
        
        $formattedMessage = $this->formatMessage($level, $message, $context);
        
        return error_log($formattedMessage . PHP_EOL, 3, $logFile);
    }
    
    protected function shouldLog($level)
    {
        return $this->levels[$level] <= $this->levels[$this->minLevel];
    }
    
    protected function formatMessage($level, $message, array $context = [])
    {
        $timestamp = date($this->dateFormat);
        $level = strtoupper($level);
        
        if (!empty($context)) {
            $message = $this->interpolate($message, $context);
        }
        
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 4);
        $caller = $backtrace[3] ?? $backtrace[2] ?? [];
        $file = basename($caller['file'] ?? 'unknown');
        $line = $caller['line'] ?? 0;
        
        return sprintf('[%s] %s: %s [%s:%d]', $timestamp, $level, $message, $file, $line);
    }
    
    protected function interpolate($message, array $context = [])
    {
        $replace = [];
        
        foreach ($context as $key => $val) {
            if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                $replace['{' . $key . '}'] = $val;
            } elseif (is_array($val) || is_object($val)) {
                $replace['{' . $key . '}'] = json_encode($val);
            }
        }
        
        return strtr($message, $replace);
    }
    
    protected function rotateLogFile($logFile)
    {
        if (!file_exists($logFile)) {
            return;
        }
        
        if (filesize($logFile) > $this->maxFileSize) {
            $rotatedFile = $logFile . '.' . date('Y-m-d-His');
            rename($logFile, $rotatedFile);
            
            $this->cleanOldLogs();
        }
    }
    
    protected function cleanOldLogs()
    {
        $files = glob($this->logPath . $this->logFile . '.*');
        
        if (count($files) > 5) {
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            $filesToDelete = array_slice($files, 0, count($files) - 5);
            
            foreach ($filesToDelete as $file) {
                unlink($file);
            }
        }
    }
    
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;
        return $this;
    }
    
    public function setMinLevel($level)
    {
        if (isset($this->levels[$level])) {
            $this->minLevel = $level;
        }
        
        return $this;
    }
    
    public function setLogFile($filename)
    {
        $this->logFile = sanitize_file_name($filename);
        return $this;
    }
    
    public function getLogPath()
    {
        return $this->logPath . $this->logFile;
    }
    
    public function clear()
    {
        $logFile = $this->logPath . $this->logFile;
        
        if (file_exists($logFile)) {
            return unlink($logFile);
        }
        
        return true;
    }
}