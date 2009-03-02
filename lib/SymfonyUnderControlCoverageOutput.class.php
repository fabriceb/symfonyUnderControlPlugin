<?php

require_once(dirname(__FILE__) . '/SymfonyUnderControlOutputInterface.php');

class SymfonyUnderControlCoverageOutput extends SymfonyUnderControlOutput implements SymfonyUnderControlOutputInterface 
{
  protected $path;
  protected $coverage_task;
  protected $output;
  
  /**
   * Write the coverage results to a file
   *
   * @TODO get as many hardcoded dummy properties out of here and replace them with actual figures
   */
  public function writeToFile()
  {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>
<metrics files="' . count($this->tests) . '" functions="0" cls="' . count($this->tests) . '" clsa="0" clsc="0" roots="0" leafs="0" interfs="0" maxdit="0">';
    foreach ( $this->tests[SymfonyUnderControlTest::TEST_UNIT] as $test )
    {
      $xml .= '<file name="' . $test->getLibFile() . '" classes="1" functions="0" loc="0" cloc="0" ncloc="0" locExecutable="0" locExecuted="0" coverage="' . $test->getCoverage() . '">
                   <class name="' . $test->getName() . '" loc="0" locExecutable="0" locExecuted="0" aif="0.000000" ahf="100.000000" ca="0" ce="0" csz="0" cis="0" coverage="' . $test->getCoverage() . '" dit="0" i="1.000000" impl="0" mif="0.000000" mhf="0.000000" noc="0" pf="0.000000" vars="2" varsnp="0" varsi="2" wmc="10" wmcnp="10" wmci="10">
                     <method name="main" loc="0" locExecutable="0" locExecuted="0" coverage="' . $test->getCoverage() . '" ccn="0" crap="6.000000" npath="0" parameters="0"/>
                   </class>
               </file>';
    }
    $xml .= '</metrics>';
    
    file_put_contents($this->path . '/undercontrol.coverage.xml', $xml);
  }
}