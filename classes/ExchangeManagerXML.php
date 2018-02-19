<?php

/**
 * Export manager from 1C to bitrix realizing pattern Strategy
 * Class ExchangeManagerXML
 */

class ExchangeManagerXML
{
    // Папка в которо лежат файлы импорта
    private $exchangeDir;
    // Путь до XML файла
    private $filePath;
    // Класс логгер
    private $logger;

    /**
     * ExchangeManagerXML constructor.
     * @param string $exchangeDir
     * @param string $fileName
     */
    function __construct($exchangeDir, $fileName)
    {
        $this -> exchangeDir = $exchangeDir;
        $this -> filePath = $_SERVER['DOCUMENT_ROOT'] . $exchangeDir . $fileName;
    }

    /**
     * Recursive convert from XML object to array
     * @param SimpleXMLElement|array $data
     * @return array
     */
    private function objectToArray($data)
    {
        if(is_array($data) || is_object($data)){
            $result = array();
            $data = (array) $data;
            foreach ($data as $key => $value) {
                $result[$key] = $this -> objectToArray($value);
            }
            return $result;
        }
        return $data;
    }


    /**
     * @param $logger Logger Setter
     * @return bool
     */
    public function setLogger($logger)
    {
        if($logger instanceof Logger) {
            $this->logger = $logger;
            return true;
        } else{
            return false;
        }
    }

    /**
     * Initializing strategy (different for different imports)
     * @param $strategy
     */
    public function startImportStrategy($strategy)
    {
        if($this -> logger){
            $data = simplexml_load_file($this -> filePath);

            if($data){
                $data = $this -> objectToArray($data);
                $strategy -> start($data, $this -> logger);
            }
            else{
                echo "Error - File not exist";
            }
        } else{
            echo "Error - Logger is not initialized";
        }
    }
}