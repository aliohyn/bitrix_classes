<?php

Class Logger
{
    private $show = true;

    // Дата время сообщения
    private $dateTime;

    // Папка с логами относительно корня сайта
    private $logDir;

    // Тип обмена
    private $type;

    /**
     * Logger constructor.
     * @param $type string name of output file
     * @param $dir string directory of logs
     */
    function __construct($type, $dir)
    {
        if(!$type){
            echo "ERROR in class Logger: empty parameter TYPE";
        } elseif(!$dir){
            echo "ERROR in class Logger: empty parameter DIR";
        } else{
            if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $dir)  || substr($dir, -1)!= "/"){
                echo "ERROR in class Logger: wrong log directory $dir";
            } else{
                $this -> logDir = $dir;
            }

            $this -> type = $type;
        }
    }

    /**
     * @param $message string critical error text
     */
    private function critical($message)
    {
        $message = "CRITICAL: [" . $this -> type . '] ' . $message;
        $this -> outputText($message);
	}

    /**
     * @param $message string simple error text
     */
    private function error($message)
    {
        $message = "ERROR: [" . $this -> type . '] ' . $message;
        $this -> outputText($message);
	}


    /**
     * @param $message string debug text
     */
    private function debug($message){
        $message = "DEBUG: [" . $this -> type . '] ' . $message;
        $this -> outputText($message);
	}

    /**
     * prepare output text
     * @param $message string
     */
    private function outputText($message)
    {
        // Добавляем текущее время
        $str = $this -> dateTime . " " . $message . "\n";

        // Создание папки с именем текущей даты
        $curDate = date("Y-m-d");
        if(!file_exists($_SERVER['DOCUMENT_ROOT'] . $this -> logDir . $curDate)){
            mkdir($_SERVER['DOCUMENT_ROOT'] . $this -> logDir . $curDate, 0755);
        }

        // Записываем в файл
        $logPath = $_SERVER['DOCUMENT_ROOT'] . $this -> logDir . $curDate . "/" . $this -> type . ".txt";
        file_put_contents($logPath, $str, FILE_APPEND);

        if($this -> show)echo "<br>" . $str . "<br>";
    }

	public function log($level, $message)
    {
        if($this -> logDir) {
            $this->dateTime = date("H:i:s");

            if ($level == "ERROR") {
                $this -> error($message);
            } elseif ($level == "CRITICAL") {
                $this -> critical($message);
            } elseif ($level == "DEBUG") {
                $this -> debug($message);
            }
        }
	}
}