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
    $this->tests [$test->getType()] [] = $test;
  }
  

}