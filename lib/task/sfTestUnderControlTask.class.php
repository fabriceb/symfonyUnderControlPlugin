<?php
/**
 * Task to execute the symfonyUnderControl testing
 * 
 * @package symfonyUnderControlPlugin
 * @author Stefan Koopmanschap <stefan.koopmanschap@symfony-project.com>
 *
 */
class sfTestUnderControlTask extends sfBaseTask
{
	
	/**
	 * @see sfTask
	 */
  protected function configure()
  {
    $this->aliases = array('test-undercontrol');
    $this->addArguments(array(
      new sfCommandArgument('path', sfCommandArgument::REQUIRED, 'Output path for XML'),
    ));
    $this->namespace = 'test';
    $this->name = 'undercontrol';
    $this->briefDescription = 'Launches all tests for use with phpUnderControl';

    $this->detailedDescription = <<<EOF
The [test:undercontrol|INFO] task launches all unit and functional tests for use with phpUnderControl:

  [./symfony test:undercontrol /dir/to/cruisecontrol/project/build/output.xml|INFO]

The task launches all tests found in [test/|COMMENT] and writes the output in phpUnderControl-compatible XML to [PATH|COMMENT].

If one or more test fail, you can try to fix the problem by launching
them by hand or with the [test:unit|COMMENT] and [test:functional|COMMENT] task.
EOF;
  }
  
  /**
   * @see sfTask
   */
  protected function execute($arguments = array(), $options = array())
  {
    $output = new SymfonyUnderControlOutput($arguments['path']);
  	$test_dir = sfConfig::get('sf_test_dir');
  	
    $finder = sfFinder::type('file')->follow_link()->name('*Test.php');
		$tests = $finder->in($test_dir . '/unit');

		foreach($tests as $test)
		{
			$testObj = new SymfonyUnderControlTest($test, SymfonyUnderControlTest::TEST_UNIT);		
			$testObj->runTest($output);
		}
		
		$functests = $finder->in($test_dir . '/functional');
		
		foreach($functests as $functest)
		{
		  $testObj = new SymfonyUnderControlTest($functest, SymfonyUnderControlTest::TEST_FUNC);
		  $testObj->runTest($output);
		}
		
		$output->writeToFile();
  }
}