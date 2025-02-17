<?php
namespace ChristianBudde\Part\log;
use ChristianBudde\Part\BackendSingletonContainer;
use ChristianBudde\Part\controller\ajax\type_handler\TypeHandler;
use ChristianBudde\Part\util\file\DumpFile;
use ChristianBudde\Part\util\file\LogFileImpl;
use ChristianBudde\Part\util\file\StubLogFileImpl;

/**
 * User: budde
 * Date: 6/3/12
 * Time: 9:38 PM
 */
class LoggerImpl implements Logger
{

    private $logFile;
    private $container;

    function __construct(BackendSingletonContainer $container, $filePath)
    {
        $this->container = $container;
        $this->logFile = $filePath == "" ? new StubLogFileImpl() : new LogFileImpl($filePath);
    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function emergency($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function alert($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function critical($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function error($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function warning($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function notice($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function info($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return int
     */
    public function debug($message, array $context = array())
    {
        return $this->log(Logger::LOG_LEVEL_DEBUG, $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return int
     */
    public function log($level, $message, array $context = array())
    {
        if($context != []){
            /** @var DumpFile $dumpFile */
            $dumpFile = true;

            $t = $this->logFile->log($message, $level, $dumpFile);
            $dumpFile->writeSerialized($context);
        } else {
            $t = $this->logFile->log($message, $level);
        }

        return $t;

    }

    /**
     * Use boolean or to combine which loglevels you whish to list.
     * @param int $level
     * @param bool $includeContext If false context will not be included in result.
     * @param int $time The earliest returned entry will be after this value
     * @return mixed
     */
    public function listLog($level = Logger::LOG_LEVEL_ALL, $includeContext = true, $time = 0)
    {
        $list = $this->logFile->listLog($level, $time);
        $result = [];
        foreach ($list as $entry) {
            if (isset($entry['dumpfile'])) {

                if ($includeContext) {
                    /** @var DumpFile $dumpFile */
                    $dumpFile = $entry['dumpfile'];

                    $entry['context'] = $dumpFile->getUnSerializedContent()[0];
                }


                unset($entry['dumpfile']);

            }
            $result[] = $entry;

        }

        return $result;
    }

    /**
     * Returns the context corresponding to the line given.
     * @param int $time
     * @return array
     */
    public function getContextAt($time)
    {
        $l = $this->listLog(Logger::LOG_LEVEL_ALL, true, $time);
        if(!count($l) || $l[0]["time"] != $time){
            return null;
        }

        return $l[0]["context"];

    }

    /**
     * Clears the log
     * @return void
     */
    public function clearLog()
    {
        $this->logFile->clearLog();
    }

    /**
     * @return TypeHandler
     */
    public function generateTypeHandler()
    {
        return $this->container->getTypeHandlerLibraryInstance()->getLoggerTypeHandlerInstance($this);
    }
}
