<?php

Interface SymfonyUnderControlOutputInterface
{
  public function __construct($path);
  public function setTest(SymfonyUnderControlTest $test);
  public function writeToFile();
}