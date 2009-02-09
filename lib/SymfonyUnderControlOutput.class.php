<?php

/**
 * Class controlling the output of the test results
 * 
 * @package symfonyUnderControlPlugin
 * @author Stefan Koopmanschap <stefan.koopmanschap@symfony-project.com>
 *
 */
class SymfonyUnderControlOutput
{
  protected $tests = array();
  protected $path;
  protected $xml;
  
  /**
   * Constructor
   *
   * @param string $path path to write the resulting XML to
   */
  public function __construct($path)
  {
    $this->path = $path;
  }
  
  /**
   * Set the current test and add it to the list of tests
   *
   * @param SymfonyUnderControlTest $test
   */
  public function setTest(SymfonyUnderControlTest $test)
  {
    $this->tests [] = $test;
  }
  
  /**
   * Write the XML to the file specified in the constructor
   *
   */
  public function writeToFile()
  {
    if (! is_writable($this->path))
    {
      throw new Exception('Path <' . $this->path . '> not writable');
    }
    $xml = $this->buildXML();
    file_put_contents($this->path, $xml);
  }
  
  /**
   * Build the XML from the test results
   *
   * @return string xUnit XML
   */
  public function buildXML()
  {
    $this->loadBaseXML();
    
    $alltests = $this->addTestSuite('All Tests');
    
    $failure_count = 0;
    $test_count = 0;
    $assertion_count = 0;
    $total_time = 0;
    
    foreach ( $this->tests as $test )
    {
      $test_count ++;
      $asserts = $test->getAsserts();
      
      $current_suite = $this->addTestSuite($test->getName(), $alltests);
      
      $current_case = $this->addTestcase($current_suite, $test->getName());
      $current_case ['file'] = $test->getFilename();
      $current_case ['assertions'] = $test->getNumberOfAssertions();
      $current_case ['time'] = $test->getTimeSpent();
      
      $assertion_count = $assertion_count + $test->getNumberOfAssertions();
      $total_time = $total_time + $test->getTimeSpent();
      
      foreach ( $asserts as $assert_number => $assert )
      {
        if (! empty($assert_number))
        {
          if (false === $assert ['status'])
          {
            $failure_count ++;
            $this->addFailure($current_case, $assert ['comment']);
          }
        }
      }
    }
    
    $alltests ['tests'] = $test_count;
    $alltests ['assertions'] = $assertion_count;
    $alltests ['failures'] = $failure_count;
    $alltests ['errors'] = 0;
    $alltests ['time'] = $total_time;
    
    return $this->xml->asXml();
  }
  
  protected function loadBaseXML()
  {
    $this->xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <testsuites></testsuites>');
  }
  
  protected function addTestSuite($name, $suite = '')
  {
    if (empty($suite))
    {
      $returnsuite = $this->xml->addChild('testsuite');
    }
    else
    {
      $returnsuite = $suite->addChild('testsuite');
    }
    $returnsuite ['name'] = $name;
    return $returnsuite;
  }
  
  protected function getTestSuite($name)
  {
    return $this->xml->xpath("//testsuite[@name='" . $name . "']'");
  }
  
  protected function addTestcase($suite, $name, $value = null)
  {
    $returncase = $suite->addChild('testcase', $value);
    $returncase ['name'] = $name . 'class';
    return $returncase;
  }
  
  protected function getTestcase($name)
  {
    return $this->xml->xpath("//testcase[@name='" . $name . "']'");
  }
  
  protected function addFailure($testcase, $failure)
  {
    $failure = $testcase->addChild('failure', $failure);
    $failure ['type'] = 'UnderControlFailure';
    return $failure;
  }
}