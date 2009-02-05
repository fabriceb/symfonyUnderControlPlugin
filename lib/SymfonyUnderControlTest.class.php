<?php

require_once sfConfig::get('sf_symfony_lib_dir') . '/vendor/lime/lime.php';

class SymfonyUnderControlTest
{
  const TOO_LITTLE_TESTS = 1;
  const TOO_MUCH_TESTS = 2;
  const RESULT_LINE = 3;
  const FAILED_TESTS = 4;
  const RETURN_STATUS = 5;
  const COMMENT = 6;
  const UNKNOWN = 0;
  
  protected $filename;
  protected $php_cli;
  protected $output;
  protected $asserts = array();
  protected $failed = 0;
  protected $current_assert;
  protected $time_spent;
  
  public function __construct($filename)
  {
    $this->filename = $filename;
    $this->php_cli = $this->find_php_cli();
  }
  
  public function runTest($output, $test_dir)
  {
    $output->setTest($this);
    
    ob_start();
    $time_start = microtime(true);
    passthru(sprintf('%s "%s" 2>&1', $this->php_cli, $this->filename), $return);
    $time_end = microtime(true);
    $this->output = ob_get_contents();
    ob_end_clean();
    
    $this->time_spent = $time_end - $time_start;
    
    $this->parseTestOutput();
  }
  
  public function getName()
  {
    $pieces = explode('/', $this->filename);
    return str_replace('.php', '', $pieces[count($pieces) - 1]);
  }
  
  public function getFilename()
  {
  	return $this->filename;
  }
  
  public function getNumberOfAssertions()
  {
  	return count($this->asserts);
  }
  
  public function getNumberOfFails()
  {
  	return $this->failed;
  }
  
  public function getAsserts()
  {
  	return $this->asserts;
  }
  
  public function getTimeSpent()
  {
  	return $this->time_spent;
  }
  
  protected function parseTestOutput()
  {
    $lines = explode("\n", $this->output);
    foreach ( $lines as $line )
    {
      $this->parseLine($line);
    }
  }
  
  /**
   * Parse the specified line
   *
   * @param string $line
   */
  protected function parseLine($line)
  {
  	$line = trim($line);
  	if ('not ok' == substr($line, 0, 6))
  	{
      $this->reportFail($this->fetchAssertNumber($line));
  	}
  	if ('ok' == substr($line, 0, 2))
  	{
  		$this->reportSuccess($this->fetchAssertNumber($line));
  	}
  	if ('#' == substr($line, 0, 1))
  	{
  		$this->reportComment($line);
  	}
  }
  
  protected function fetchAssertNumber($line)
  {
  	$line = trim($line);
  	$last_space = strrpos($line, ' ');
  	return substr($line, $last_space+1);
  }
  
  protected function reportFail($assert_number)
  {
  	$this->current_assert = $assert_number;
  	$this->asserts[$assert_number] = array();
  	$this->asserts[$assert_number]['status'] = false;
  	$this->failed++;
  }
  
  protected function reportSuccess($assert_number)
  {
  	$this->current_assert = $assert_number;
    $this->asserts[$assert_number] = array();
  	$this->asserts[$assert_number]['status'] = true;
  }
  
  protected function reportComment($content)
  {
  	if (!isset($this->asserts[$this->current_assert]['comment']))
  	{
  		$this->asserts[$this->current_assert]['comment'] = $content;
  	}
  	else 
  	{
  		$this->asserts[$this->current_assert]['comment'] .= "\n" . $content;
  	}
  }
  
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