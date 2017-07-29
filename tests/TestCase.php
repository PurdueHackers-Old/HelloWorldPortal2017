<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    private function prepareForTests() {
     //Log email instead of sending, regardless of config
     Mail::pretend(true);
   }
}
