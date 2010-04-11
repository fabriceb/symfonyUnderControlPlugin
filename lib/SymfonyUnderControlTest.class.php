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

  const TEST_UNIT = 'Unit';
  const TEST_FUNC = 'Functional';

  protected $filename;
  protected $lib_file;
  protected $php_cli;
  protected $output;
  protected $coverage_output;
  protected $coverage_results = array();
  protected $asserts = array();
  protected $failed = 0;
  protected $current_assert;
  protected $time_spent;
  protected $type;
  protected $coverage;

  /**
   * Constructor for a test
   *
   * @param string $filename path to the file that contains the test
   */
  public function __construct($filename, $type = self::TEST_UNIT)
  {
    $this->filename = $filename;
    $this->php_cli = $this->find_php_cli();
    $this->type = $type;
  }

  /**
   * Run this test
   *
   * @param SymfonyUnderControlTestOutput $output
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
    echo $this->output;

    $this->time_spent = $time_end - $time_start;

    $this->parseTestOutput();
  }

  /**
   * Run code coverage on this test
   *
   * @param SymfonyUnderControlCoverageOutput $output
   */
  public function runCoverage($output)
  {
    $output->setTest($this);

    $this->lib_file = $this->getLibraryFile();
    // only run the test if the library file according to our logic exists
    if (is_file($this->lib_file))
    {
      $test_command = sfConfig::get('sf_root_dir') . '/symfony test:coverage ' . $this->getRelativePath($this->filename) . ' ' . $this->getRelativePath($this->lib_file);

      ob_start();
      passthru(sprintf('%s %s 2>&1', $this->php_cli, $test_command), $return);
      $this->coverage_output = ob_get_contents();
      ob_end_clean();

      $this->parseCoverageOutput();
    }
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
   * Get the type of test
   *
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }

  /**
   * Get the coverage as gotten from the code coverage check
   *
   * @return integer
   */
  public function getCoverage()
  {
    return $this->coverage;
  }

  public function getLibFile()
  {
    return $this->lib_file;
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
   * Parse the code coverage output of the test
   *
   */
  protected function parseCoverageOutput()
  {
    $parts = explode("\n", $this->coverage_output);
    foreach ( $parts as $part )
    {
      $test = explode(' ', $part);
      if (strpos($test [0], '.class'))
      {
        $this->coverage = str_replace('%', '', $test[count($test) - 1]);
      }
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
    if ('PHP Fatal error:' == substr($line, 0, 16))
    {
      $this->reportFail();
      $this->reportComment($line);
    }
    if ('PHP Warning:' == substr($line, 0, 12))
    {
      $this->reportFail();
      $this->reportComment($line);
    }
    if ('not ok' == substr($line, 0, 6))
    {
      $this->reportFail();
    }
    if ('ok' == substr($line, 0, 2))
    {
      $this->reportSuccess();
    }
    if ('#' == substr($line, 0, 1))
    {
      $this->reportComment($line);
    }
  }


  /**
   * Report a failed test into the assertion results
   *
   * @param integer $assert_number
   */
  protected function reportFail()
  {
    $this->current_assert++;
    $this->asserts[$this->current_assert] = array();
    $this->asserts[$this->current_assert]['status'] = false;
    $this->failed++;
  }

  /**
   * Report a successful test into the assertion results
   *
   * @param integer $assert_number
   */
  protected function reportSuccess()
  {
    $this->current_assert++;
    $this->asserts[$this->current_assert] = array();
    $this->asserts[$this->current_assert]['status'] = true;
  }

  /**
   * Report a comment into the assertion results when necessary
   *
   * @param string $content
   */
  protected function reportComment($content)
  {
    if (! isset($this->asserts[$this->current_assert] ['comment']))
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

  /**
   * Get the library file path that is being tested (if existing)
   *
   * @return string
   */
  protected function getLibraryFile()
  {
    $testfile = basename($this->filename);
    $libfile = str_replace('Test.php', '.class.php', $testfile);
    $result = sfFinder::type('file')->name($libfile)->in(sfConfig::get('sf_lib_dir'));
    if (isset($result [0]))
    {
      return $result [0];
    }
  }

  protected function getRelativePath($file)
  {
    return str_replace(sfConfig::get('sf_root_dir') . '/', '', $file);
  }

}