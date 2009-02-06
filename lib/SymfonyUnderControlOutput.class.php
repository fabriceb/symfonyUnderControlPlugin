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
  	$xml = '';
  	
  	$failure_count = 0;
  	$test_count = 0;
  	$assertion_count = 0;
  	$total_time = 0;
  	
    foreach ( $this->tests as $test )
    {
    	$test_count++;
      $asserts = $test->getAsserts();
      $xml .= '<testcase name="' . $test->getName() . 'class" file="' . $test->getFilename() . '" assertions="' . $test->getNumberOfAssertions() . '" time="' . $test->getTimeSpent() . '">';
      $assertion_count = $assertion_count + $test->getNumberOfAssertions();
      $total_time = $total_time + $test->getTimeSpent();
      
      foreach($asserts as $assert_number => $assert)
      {
      	if (!empty($assert_number))
      	{
	      	if (false === $assert['status'])
	      	{
	      		$failure_count++;
	      		$xml .= '<failure type="UnderControlFailure"><![CDATA[' . $assert['comment'] . ']]></failure>' . "\n";
	      	}
      	}
      }
      $xml .= '</testcase>';
    }

    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <testsuites>
              <testsuite name="All Tests" tests="' . $test_count . '" assertions="' . $assertion_count . '" failures="' . $failure_count . '" errors="0" time="' . $total_time . '"><testsuite name="Unit" file="' . dirname($test->getFilename()) . '" fullPackage="UnderControlTests" category="QualityAssurance" package="UnderControlTests" tests="' . $test_count . '" assertions="' . $assertion_count . '" failures="' . $failure_count . '" errors="0" time="' . $total_time . '">' . "\n" . $xml . '</testsuite>
             </testsuite>
              </testsuites>';
    
    $sxml = simplexml_load_string($xml);
    return $sxml->asXml();
  }

}