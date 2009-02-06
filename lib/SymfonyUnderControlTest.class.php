<?php

// require lime for testing usage
require_once sfConfig::get('sf_symfony_lib_dir') . '/vendor/lime/lime.php';

/**
 * Class representing a single test file.
 * 
 * @package symfonyUnderControlPlugin
 * @author Stefan Koopmanschap <stefan.koopmanschap@symfony-project.com>
 *
 */
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
  
  /**
   * Constructor for a test
   *
   * @param string $filename path to the file that 
   */
  public function __construct($filename)
  {
    $this->filename = $filename;
    $this->php_cli = $this->find_php_cli();
  }
  
  /**
   * Run this test
   *
   * @param SymfonyUnderControlOutput $output
   */
  public function runTest($output)
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
  
  /**
   * Getter for the test name
   *
   * @return string name of this test
   */
  public function getName()
  {
    $pieces = explode('/', $this->filename);
    return str_replace('.php', '', $pieces[count($pieces) - 1]);
  }
  
  /**
   * Getter for the filename of this test
   *
   * @return string filename of this test
   */
  public function getFilename()
  {
  	return $this->filename;
  }
  
  /**
   * Get the number of assertions in this test
   *
   * @return integer number of assertions in this test
   */
  public function getNumberOfAssertions()
  {
  	return count($this->asserts);
  }
  
  /**
   * Get the number of failed assertions in this test
   *
   * @return integer number of failed assertions in this test
   */
  public function getNumberOfFails()
  {
  	return $this->failed;
  }
  
  /**
   * Get the assertion results
   *
   * @return array with the results of the assertions
   */
  public function getAsserts()
  {
  	return $this->asserts;
  }
  
  /**
   * Get the time spent to run the test
   *
   * @return float time spent on running the test
   */
  public function getTimeSpent()
  {
  	return $this->time_spent;
  }
  
  /**
   * Parse the output of the test
   *
   */
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
  
  /**
   * Fetch the assertion number from the given line
   *
   * @param string $line
   * @return integer
   */
  protected function fetchAssertNumber($line)
  {
  	$line = trim($line);
  	$last_space = strrpos($line, ' ');
  	return (int) substr($line, $last_space+1);
  }
  
  /**
   * Report a failed test into the assertion results
   *
   * @param integer $assert_number
   */
  protected function reportFail($assert_number)
  {
  	$this->current_assert = $assert_number;
  	$this->asserts[$assert_number] = array();
  	$this->asserts[$assert_number]['status'] = false;
  	$this->failed++;
  }
  
  /**
   * Report a successful test into the assertion results
   *
   * @param integer $assert_number
   */
  protected function reportSuccess($assert_number)
  {
  	$this->current_assert = $assert_number;
    $this->asserts[$assert_number] = array();
  	$this->asserts[$assert_number]['status'] = true;
  }
  
  /**
   * Report a comment into the assertion results when necessary
   *
   * @param string $content
   */
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