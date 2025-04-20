<?php
namespace App\Service\Version;

use App\Utils\Env;

class Version {

  private ?string $_branch=null;
  private array $_logs=[];

  public function findGitDir(): string {
    return realpath(__DIR__.'/../../../.git');
  }

  public function getLogs(): array {
    if(count($this->_logs) === 0)
      return [ ['date' => '-','action' => '-','to' => '-'] ];
    return $this->_logs;
  }

  function __construct() {
    $path = $this->findGitDir();
    if($path)
      $this->_branch = str_replace("\n","",preg_replace("/^(.*)\/([^\/]+)$/","$2",file_get_contents($path.'/HEAD')));
    if(true !== Env::get('WITH_VERSION'))
      $this->_branch = "";
    $this->setLogs();
  }

  public function getBranch(): ?string { return $this->_branch; }

  public function setLogs($rowCount = 5): void
  {
    $this->_logs=[];
    $dir = $this->findGitDir();
    $logHead = $dir . '/logs/HEAD';
    if (!$dir || !is_readable($logHead)) {
      return;
    }
    $fp = fopen($logHead, 'r');
    fseek($fp, -1, SEEK_END);
    $pos = ftell($fp);
    $log = "";
    $rowCounter = -1;
    while ($rowCounter <= $rowCount && $pos >= 0) {
      $char = fgetc($fp);
      $log = $char . $log;
      if ($char == "\n")
        $rowCounter++;
      fseek($fp, $pos--);
    }
    $result = [];
    foreach (explode("\n", trim($log)) as $row) {
      $input = [];
      if(preg_match("/^(?<from>\w+)[ ]+(?<to>\w+)[ ]+(?<user>.*)[ ]+[<](?<email>[^>]+)[>][ ]+(?<timestamp>\d+)[ ]+(?<region>.*)\t(?<action>.*)$/",$row, $input)) {
        $tmp = new \DateTime('@'.$input['timestamp']);
        $input['date']= $tmp->format('d/m/Y H:i');
        $result[] = $input;
      }
    }
    $this->_logs = array_reverse($result);
  }
}
