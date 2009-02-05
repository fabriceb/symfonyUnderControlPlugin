<?php

class SymfonyUnderControlOutput
{
  protected $tests = array();
  protected $current_test;
  protected $path;
  
  public function __construct($path)
  {
    $this->path = $path;
  }
  
  public function setTest(SymfonyUnderControlTest $test)
  {
    $this->current_test = $test;
    $this->tests [] = $test;
  }
  
  public function writeToFile()
  {
    if (! is_writable($this->path))
    {
      throw new Exception('Path <' . $this->path . '> not writable');
    }
    $xml = $this->buildXML();
  	file_put_contents($this->path, $xml);
  }
  
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
  
  public function __call($name, $arguments)
  {
    throw new Exception('Method ' . $name . ' not implemented yet');
    echo $name . ':' . print_r($arguments, 1) . "\n";
  }

}