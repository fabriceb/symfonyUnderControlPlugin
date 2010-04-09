<?php

pake_desc('Convert Tests output to XML');
pake_task('phpunit-tests');

function run_phpunit_tests($task, $args)
{

  if (count($args) == 0)
  {
    throw new Exception('You must provide an output path.');
  }

  $output = new SymfonyUnderControlTestOutput($args[0]);
  $test_dir = sfConfig::get('sf_test_dir');
   
  $finder = sfFinder::type('file')->follow_link()->name('*Test.php');
  $tests = $finder->in($test_dir . '/unit');

  $tests=array();

  $testObjects = array();

  foreach($tests as $test)
  {
    $testObj = new SymfonyUnderControlTest($test, SymfonyUnderControlTest::TEST_UNIT);
    $testObjects[] = $testObj;
    $testObj->runTest($output);
  }

  $functests = $finder->in($test_dir . '/functional');

  $functests = array('/allomatch/htdocs/branches/dev/test/functional/iphone/matchActionsTest.php');

  foreach($functests as $functest)
  {
    $testObj = new SymfonyUnderControlTest($functest, SymfonyUnderControlTest::TEST_FUNC);
    $testObj->runTest($output);
  }

  $output->writeToFile();
}
