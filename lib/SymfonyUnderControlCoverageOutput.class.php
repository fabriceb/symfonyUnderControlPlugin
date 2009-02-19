<?php

class SymfonyUnderControlCoverageOutput
{
  protected $path;
  protected $coverage_task;
  protected $output;
  protected $results = array();
  
  public function __construct($dispatcher, $formatter, $command_application, $path)
  {
    require_once (sfConfig::get('sf_symfony_lib_dir') . '/task/test/sfTestCoverageTask.class.php');
    $this->path = $path;
    $this->php_cli = $this->find_php_cli();
    $this->coverage_task = new sfTestCoverageTask($dispatcher, $formatter);
    $this->coverage_task->setCommandApplication($command_application);
    $this->run();
  }
  
  public function writeToFile()
  {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<metrics files="' . count($this->results) . '" functions="0" cls="' . count($this->results) . '" clsa="0" clsc="0" roots="0" leafs="0" interfs="0" maxdit="0">';
    foreach ( $this->results as $file => $coverage )
    {
      $xml .= '<file name="' . $file . '" classes="1" functions="0" loc="0" cloc="0" ncloc="0" locExecutable="0" locExecuted="0" coverage="' . $coverage . '">
                   <class name="' . $file . '" loc="0" locExecutable="0" locExecuted="0" aif="0.000000" ahf="100.000000" ca="0" ce="0" csz="0" cis="0" coverage="' . $coverage . '" dit="0" i="1.000000" impl="0" mif="0.000000" mhf="0.000000" noc="0" pf="0.000000" vars="2" varsnp="0" varsi="2" wmc="10" wmcnp="10" wmci="10">
                     <method name="main" loc="0" locExecutable="0" locExecuted="0" coverage="' . $coverage . '" ccn="0" crap="6.000000" npath="0" parameters="0"/>
                   </class>
               </file>';
    }
    $xml .= '</metrics>';
    
    file_put_contents(str_replace('.xml', '.coverage.xml', $this->path), $xml);
  }
  
  protected function run()
  {
    $test_command = sfConfig::get('sf_root_dir') . '/symfony test:coverage test/unit lib';
    
    ob_start();
    passthru(sprintf('%s %s 2>&1', $this->php_cli, $test_command), $return);
    $this->output = ob_get_contents();
    ob_end_clean();
    /*ob_start();
    $this->coverage_task->run(array('/test/unit', '/lib'), array());
    $this->output = ob_get_contents();
    ob_end_clean();*/
    $this->parseOutput();
  }
  
  protected function parseOutput()
  {
    $parts = explode("\n", $this->output);
    foreach ( $parts as $part )
    {
      $test = explode(' ', $part);
      if (strpos($test[0], '.class'))
      {
        $this->results [$test [0]] = str_replace('%', '', $test [count($test) - 1]);
      }
    }
  }
  
  /**
   * Find the php CLI to use use for running the test
   * 
   * This method has been shamelessly copied from lime
   * 
   * @author Fabien Potencier <fabien.potencier@gmail.com>
   * @return string
   * @throws Exception
   */
  protected function find_php_cli()
  {
    $path = getenv('PATH') ? getenv('PATH') : getenv('Path');
    $exe_suffixes = DIRECTORY_SEPARATOR == '\\' ? (getenv('PATHEXT') ? explode(PATH_SEPARATOR, getenv('PATHEXT')) : array('.exe', '.bat', '.cmd', '.com')) : array('');
    foreach ( array('php5', 'php') as $php_cli )
    {
      foreach ( $exe_suffixes as $suffix )
      {
        foreach ( explode(PATH_SEPARATOR, $path) as $dir )
        {
          $file = $dir . DIRECTORY_SEPARATOR . $php_cli . $suffix;
          if (is_executable($file))
          {
            return $file;
          }
        }
      }
    }
    
    throw new Exception("Unable to find PHP executable.");
  }
}